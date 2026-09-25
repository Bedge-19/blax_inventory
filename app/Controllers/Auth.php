<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\ShopModel;

class Auth extends BaseController
{
    public function login()
    {
        $session = session();
        if ($session->get('isLoggedIn')) {
            return $this->redirectUserRole($session->get('user_role'));
        }

        if ($this->request->getMethod() === 'POST') {
            $email    = trim($this->request->getPost('email') ?? '');
            $password = trim($this->request->getPost('password') ?? '');

            if ($email === '' || $password === '') {
                session()->setFlashdata('error', 'Email and password are required.');
                return redirect()->to('/login');
            }

            $userModel = new UserModel();
            $user      = $userModel->findByEmail($email);

            if ($user && password_verify($password, $user['password_hash'])) {
                $shopId = null;
                if ($user['role'] === 'shop_owner') {
                    $shopModel = new ShopModel();
                    $verification = $shopModel->getOwnerVerificationState((int) $user['id']);
                    $shop = $verification['shop'];

                    if (($user['status'] ?? 'active') !== 'active' || $verification['state'] !== 'active') {
                        $message = match ($verification['state']) {
                            'rejected'  => 'Your tenant application was not approved.' . (!empty($shop['rejection_reason']) ? ' Reason: ' . $shop['rejection_reason'] : ''),
                            'suspended' => 'Your tenant account is suspended.',
                            default     => 'Your account is being processed for verification.',
                        };
                        session()->setFlashdata('error', $message);

                        return redirect()->to('/login');
                    }

                    $shopId = $shop['id'] ?? null;
                } elseif (($user['status'] ?? 'active') !== 'active') {
                    session()->setFlashdata('error', 'Your account is not currently active.');

                    return redirect()->to('/login');
                }

                $session->regenerate();
                $session->set([
                    'user_id'          => $user['id'],
                    'user_name'        => $user['first_name'] . ' ' . $user['last_name'],
                    'user_email'       => $user['email'],
                    'user_role'        => $user['role'],
                    'shop_id'          => $shopId,
                    'profile_image_url'=> $user['profile_image_url'] ?? null,
                    'isLoggedIn'       => true,
                ]);

                return $this->redirectUserRole($user['role']);
            }

            session()->setFlashdata('error', 'Invalid email or password.');
            return redirect()->to('/login');
        }

        $roleParam = (string) ($this->request->getGet('role') ?? 'customer');
        $initialRole = in_array($roleParam, ['shop_owner', 'merchant', 'seller'], true) ? 'shop_owner' : 'customer';

        return view('auth/login', [
            'initialRole' => $initialRole,
        ]);
    }

    public function signup()
    {
        $session = session();
        if ($session->get('isLoggedIn')) {
            return redirect()->to('/');
        }

        if ($this->request->getMethod() === 'POST') {
            $firstName = trim($this->request->getPost('first_name') ?? '');
            $lastName  = trim($this->request->getPost('last_name') ?? '');
            $email     = trim($this->request->getPost('email') ?? '');
            $password  = trim($this->request->getPost('password') ?? '');
            $confirm   = trim($this->request->getPost('confirm_password') ?? '');
            $phone     = trim($this->request->getPost('phone') ?? '');

            if ($firstName === '' || $lastName === '') {
                session()->setFlashdata('error', 'First name and last name are required.');
                return redirect()->to('/signup');
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                session()->setFlashdata('error', 'A valid email address is required.');
                return redirect()->to('/signup');
            }
            if (strlen($password) < 8) {
                session()->setFlashdata('error', 'Password must be at least 8 characters long.');
                return redirect()->to('/signup');
            }
            if ($password !== $confirm) {
                session()->setFlashdata('error', 'Passwords do not match.');
                return redirect()->to('/signup');
            }

            $userModel = new UserModel();
            if ($userModel->findByEmail($email)) {
                session()->setFlashdata('error', 'An account with this email already exists.');
                return redirect()->to('/signup');
            }

            $userId = $userModel->insert([
                'role'          => 'customer',
                'first_name'    => $firstName,
                'last_name'     => $lastName,
                'email'         => $email,
                'phone'         => $phone,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'status'        => 'active',
            ]);

            $session->regenerate();
            $session->set([
                'user_id'    => $userId,
                'user_name'  => $firstName . ' ' . $lastName,
                'user_email' => $email,
                'user_role'  => 'customer',
                'isLoggedIn' => true,
            ]);

            // Notify admins of new customer registration
            $admins = $userModel->where('role', 'admin')->findAll();
            $notifModel = new \App\Models\NotificationModel();
            foreach ($admins as $admin) {
                $notifModel->create(
                    (int) $admin['id'],
                    'customer_registration',
                    'New Customer Registered',
                    "{$firstName} {$lastName} ({$email}) registered on the platform.",
                    '/admin/customers?q=' . urlencode($email)
                );
            }

            return redirect()->to('/');
        }

        return view('auth/signup');
    }

    public function forgotPassword()
    {
        if ($this->request->getMethod() === 'POST') {
            $email = trim($this->request->getPost('email') ?? '');

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return redirect()->to('/forgot-password')->with('error', 'Enter a valid email address.');
            }

            $userModel = new UserModel();
            $user = $userModel->findByEmail($email);
            if ($user) {
                // Generate a secure 32-byte token
                $rawToken = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $rawToken);
                $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

                $tokenModel = new \App\Models\PasswordResetTokenModel();
                $tokenModel->insert([
                    'user_id'    => (int) $user['id'],
                    'token_hash' => $tokenHash,
                    'expires_at' => $expiresAt,
                ]);

                $resetLink = base_url('reset-password/' . $rawToken);

                try {
                    (new \App\Services\MailService())->sendPasswordReset($email, (string) ($user['first_name'] ?? 'User'), $resetLink);
                } catch (\Throwable $e) {
                    log_message('error', 'Failed to send password reset email: ' . $e->getMessage());
                }
            }

            return redirect()->to('/forgot-password')->with('success', 'If an account exists with that email address, a password reset link has been sent.');
        }

        return view('auth/forgot_password');
    }

    public function resetPassword(?string $token = null)
    {
        $token = trim((string) $token);
        if ($token === '') {
            return redirect()->to('/forgot-password')->with('error', 'Invalid password reset token.');
        }

        $tokenHash = hash('sha256', $token);
        $tokenModel = new \App\Models\PasswordResetTokenModel();
        $resetRecord = $tokenModel->findValidToken($tokenHash);

        if (!$resetRecord) {
            return redirect()->to('/forgot-password')->with('error', 'The password reset link is invalid or has expired.');
        }

        if ($this->request->getMethod() === 'POST') {
            $password = trim($this->request->getPost('password') ?? '');
            $confirm  = trim($this->request->getPost('confirm_password') ?? '');

            if (strlen($password) < 8) {
                return redirect()->back()->with('error', 'Password must be at least 8 characters long.');
            }
            if ($password !== $confirm) {
                return redirect()->back()->with('error', 'Passwords do not match.');
            }

            $userModel = new UserModel();
            $userModel->update($resetRecord['user_id'], [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            $tokenModel->update($resetRecord['id'], [
                'used_at' => date('Y-m-d H:i:s'),
            ]);

            return redirect()->to('/login')->with('success', 'Password reset successfully. You can now log in with your new password.');
        }

        return view('auth/reset_password', ['token' => $token]);
    }

    public function registerShop()
    {
        $session = session();
        if ($session->get('isLoggedIn')) {
            return $this->redirectUserRole($session->get('user_role'));
        }

        if ($this->request->getMethod() === 'POST') {
            $firstName = trim($this->request->getPost('first_name') ?? '');
            $lastName  = trim($this->request->getPost('last_name') ?? '');
            $email     = trim($this->request->getPost('email') ?? '');
            $password  = trim($this->request->getPost('password') ?? '');
            $confirm   = trim($this->request->getPost('confirm_password') ?? '');
            $shopName  = trim($this->request->getPost('shop_name') ?? '');
            $location  = trim($this->request->getPost('address_line') ?? '');
            $phone     = trim($this->request->getPost('phone') ?? '');

            if ($firstName === '' || $lastName === '') {
                session()->setFlashdata('error', 'First name and last name are required.');
                return redirect()->to('/merchant-signup');
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                session()->setFlashdata('error', 'A valid email address is required.');
                return redirect()->to('/merchant-signup');
            }
            if ($shopName === '') {
                session()->setFlashdata('error', 'Store name is required.');
                return redirect()->to('/merchant-signup');
            }
            if (strlen($password) < 8) {
                session()->setFlashdata('error', 'Password must be at least 8 characters long.');
                return redirect()->to('/merchant-signup');
            }
            if ($password !== $confirm) {
                session()->setFlashdata('error', 'Passwords do not match.');
                return redirect()->to('/merchant-signup');
            }

            $userModel = new UserModel();
            if ($userModel->findByEmail($email)) {
                session()->setFlashdata('error', 'An account with this email already exists.');
                return redirect()->to('/merchant-signup');
            }

            $permitUrl = null;
            $permit    = $this->request->getFile('business_permit');
            if (!$permit || !$permit->isValid() || $permit->hasMoved()) {
                session()->setFlashdata('error', 'A valid business permit is required.');
                return redirect()->to('/merchant-signup');
            }

            if ($permit && $permit->isValid() && !$permit->hasMoved()) {
                $allowed = ['pdf', 'png', 'jpg', 'jpeg'];
                $extension = strtolower($permit->getClientExtension());
                $mime      = strtolower((string) $permit->getMimeType());
                $allowedMime = [
                    'pdf'  => ['application/pdf'],
                    'png'  => ['image/png'],
                    'jpg'  => ['image/jpeg'],
                    'jpeg' => ['image/jpeg'],
                ];

                if (!in_array($extension, $allowed, true) || !in_array($mime, $allowedMime[$extension] ?? [], true)) {
                    session()->setFlashdata('error', 'Business permit must be a PDF, PNG or JPG file.');
                    return redirect()->to('/merchant-signup');
                }
                if ($permit->getSize() > 10485760) {
                    session()->setFlashdata('error', 'Business permit must be 10 MB or smaller.');
                    return redirect()->to('/merchant-signup');
                }

                $cloudinary = new \App\Libraries\CloudinaryService();
                $isPdf      = ($extension === 'pdf');
                $publicId   = 'permit_' . time() . '_' . bin2hex(random_bytes(4));
                $uploadRes  = null;

                if ($cloudinary->isConfigured()) {
                    try {
                        $uploadRes = $isPdf
                            ? $cloudinary->uploadRawFile($permit, \App\Libraries\CloudinaryService::FOLDER_BUSINESS_PERMITS, $publicId)
                            : $cloudinary->uploadImage($permit, \App\Libraries\CloudinaryService::FOLDER_BUSINESS_PERMITS, $publicId);
                    } catch (\Throwable $cldEx) {
                        log_message('error', '[Auth::registerShop] Cloudinary upload exception: ' . $cldEx->getMessage());
                    }
                }

                if ($uploadRes && !empty($uploadRes['secure_url'])) {
                    $permitUrl = $uploadRes['secure_url'];
                } else {
                    // Fallback to secure local storage if Cloudinary upload fails or is not configured
                    $uploadPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'business_permits';
                    try {
                        if (!is_dir($uploadPath)) {
                            @mkdir($uploadPath, 0775, true);
                        }
                        $fileName  = $permit->getRandomName();
                        $permit->move($uploadPath, $fileName);
                        $permitUrl = 'private/uploads/business_permits/' . $fileName;
                    } catch (\Throwable $moveEx) {
                        log_message('error', '[Auth::registerShop] Business permit upload fallback failed: ' . $moveEx->getMessage());
                        session()->setFlashdata('error', 'Failed to upload business permit. Please ensure the file is valid and try again.');
                        return redirect()->to('/merchant-signup');
                    }
                }
            }

            $db = \Config\Database::connect();
            $db->transStart();

            $userId = $userModel->insert([
                'role'          => 'shop_owner',
                'first_name'    => $firstName,
                'last_name'     => $lastName,
                'email'         => $email,
                'phone'         => $phone,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'status'        => 'pending',
            ]);

            $shopModel = new ShopModel();
            $shopId    = $shopModel->insert([
                'owner_id'           => $userId,
                'shop_name'          => $shopName,
                'slug'               => url_title($shopName, '-', true) . '-' . rand(100, 999),
                'description'        => 'Merchant shop registered via platform.',
                'address_line'       => $location,
                'business_permit_url'=> $permitUrl,
                'status'             => 'pending',
            ]);

            $db->transComplete();
            if ($db->transStatus() === false || !$userId || !$shopId) {
                log_message('error', 'Tenant registration could not be saved.');
                return redirect()->to('/merchant-signup')->with('error', 'Registration could not be completed. Please try again.');
            }

            // Notify admins of new merchant registration & verification request
            $admins = $userModel->where('role', 'admin')->findAll();
            $notifModel = new \App\Models\NotificationModel();
            foreach ($admins as $admin) {
                $notifModel->create(
                    (int) $admin['id'],
                    'merchant_verification',
                    'New Merchant Verification Request',
                    "New store application submitted: \"{$shopName}\" by {$firstName} {$lastName}.",
                    '/admin/tenants'
                );
            }

            $session->regenerate();
            $session->setFlashdata('success', 'Registration submitted. Your business permit is under review. You will receive an email once your account is verified.');

            return redirect()->to('/login');
        }

        return view('auth/merchant_signup');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }

    private function redirectUserRole(string $role)
    {
        if ($role === 'admin') {
            return redirect()->to('/admin/dashboard');
        } elseif ($role === 'shop_owner') {
            return redirect()->to('/tenant/dashboard');
        }
        return redirect()->to('/');
    }
}
