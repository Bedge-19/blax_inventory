<?php

namespace App\Models;

use CodeIgniter\Model;

class PayoutModel extends Model
{
    protected $table            = 'payout_requests';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'shop_id',
        'reference_number',
        'amount',
        'destination_method',
        'destination_detail',
        'fee',
        'deduction_percent',
        'status',
        'requested_at',
        'completed_at',
    ];

    public function getAdminRevenueTotal(?string $status = 'completed'): float
    {
        $builder = $this->db->table('payout_requests')->selectSum('fee', 'total');
        if ($status !== null) {
            $builder->where('status', $status);
        }
        $row = $builder->get()->getRow();
        return (float) ($row->total ?? 0);
    }

    public function getAdminRevenueByRange(string $from, ?string $to = null, string $status = 'completed'): float
    {
        $builder = $this->db->table('payout_requests')->selectSum('fee', 'total')->where('status', $status);
        $builder->where('completed_at >=', $from);
        if ($to !== null) {
            $builder->where('completed_at <', $to);
        }
        $row = $builder->get()->getRow();
        return (float) ($row->total ?? 0);
    }

    public function getAdminRevenueChartData(string $range = '30', string $status = 'completed'): array
    {
        $today = $this->getLocalToday();
        if ($range === 'year') {
            $rows = $this->db->table('payout_requests')
                ->select('MONTH(completed_at) AS m, SUM(fee) AS rev')
                ->where('status', $status)
                ->where('YEAR(completed_at) = YEAR(CURDATE())', null, false)
                ->groupBy('m')
                ->get()->getResultArray();
            $byMonth = [];
            foreach ($rows as $r) { $byMonth[(int)$r['m']] = (float)$r['rev']; }
            $labels=[]; $values=[];
            for ($m=1;$m<=12;$m++) { $labels[]=date('M', mktime(0,0,0,$m,1)); $values[]=$byMonth[$m] ?? 0.0; }
            return ['labels'=>$labels,'values'=>$values];
        }
        $days = $range === '7' ? 7 : 30;
        $start = date('Y-m-d', strtotime($today.' -'.($days-1).' days'));
        $rows = $this->db->table('payout_requests')
            ->select('DATE(completed_at) AS d, SUM(fee) AS rev')
            ->where('status', $status)
            ->where('completed_at >=', $start.' 00:00:00')
            ->groupBy('d')
            ->get()->getResultArray();
        $byDay=[];
        foreach ($rows as $r) { $byDay[$r['d']]=(float)$r['rev']; }
        $labels=[]; $values=[];
        for ($i=$days-1;$i>=0;$i--) {
            $d=date('Y-m-d', strtotime($today.' -'.$i.' days'));
            $labels[]=date('M d', strtotime($d));
            $values[]=$byDay[$d] ?? 0.0;
        }
        return ['labels'=>$labels,'values'=>$values];
    }

    public function getLocalToday(): string
    {
        return (string) $this->db->query('SELECT CURDATE() AS d')->getRow()->d;
    }

    /**
     * Paginated GCash payout requests for Admin management.
     *
     * @return array{payments: array, pager: \CodeIgniter\Pager\Pager|null}
     */
    public function getPaymentsPaginated(
        ?string $search = null,
        ?string $status = null,
        int $perPage = 15,
        int $page = 1,
        string $group = 'payments'
    ): array {
        $this->builder()
            ->select('payout_requests.*, s.shop_name, s.gcash_number')
            ->join('shops s', 's.id = payout_requests.shop_id', 'left')
            ->where('payout_requests.destination_method', 'gcash')
            ->orderBy('payout_requests.requested_at', 'DESC');

        if ($search !== null && $search !== '') {
            $this->builder()->groupStart()
                ->like('payout_requests.reference_number', $search)
                ->orLike('s.shop_name', $search)
                ->groupEnd();
        }

        if ($status !== null && in_array($status, ['pending', 'processing', 'completed', 'failed'], true)) {
            $this->builder()->where('payout_requests.status', $status);
        }

        $payments = $this->paginate($perPage, $group, $page);

        return [
            'payments' => $payments ?: [],
            'pager'    => $this->pager,
        ];
    }

    /**
     * Paginated withdrawal history for a specific shop.
     *
     * @return array{withdrawals: array, pager: \CodeIgniter\Pager\Pager|null}
     */
    public function getWithdrawalsByShopPaginated(
        int $shopId,
        int $perPage = 15,
        int $page = 1,
        string $group = 'withdrawals'
    ): array {
        $this->builder()
            ->select('payout_requests.*, payout_requests.destination_method as method, payout_requests.destination_detail as account_details, payout_requests.requested_at as created_at')
            ->where('payout_requests.shop_id', $shopId)
            ->orderBy('payout_requests.requested_at', 'DESC');

        $withdrawals = $this->paginate($perPage, $group, $page);

        return [
            'withdrawals' => $withdrawals ?: [],
            'pager'       => $this->pager,
        ];
    }
}
