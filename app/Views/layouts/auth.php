<!DOCTYPE html><html class="light" lang="en"><head>

    <?= view('components/head', ['title' => $title ?? 'Blax']) ?>

</head>

<body class="bg-background text-on-background font-body-md min-h-screen flex items-center justify-center bg-pattern p-lg">

    <main class="w-full mx-auto <?= $this->renderSection('cardClass') ?: 'max-w-md' ?>">

        <div class="glass-card rounded-[24px] p-xl flex flex-col gap-lg">

            <?php if (session()->getFlashdata('error')): ?>

                <div class="p-md rounded-lg bg-error-container text-on-error-container text-sm font-medium mb-md">

                    <?= session()->getFlashdata('error') ?>

                </div>

            <?php endif; ?>

            <?php if (session()->getFlashdata('success')): ?>

                <div class="p-md rounded-lg bg-green-100 text-green-800 text-sm font-medium mb-md">

                    <?= session()->getFlashdata('success') ?>

                </div>

            <?php endif; ?>

            <?= $this->renderSection('content') ?>

            <?= $this->renderSection('footer') ?>

        </div>

    </main>

    <?= $this->renderSection('scripts') ?>

</body></html>