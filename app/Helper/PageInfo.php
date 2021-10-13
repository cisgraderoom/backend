<?php


namespace App\Helper;

use Illuminate\Http\Response;

class PageInfo
{

    const LIMIT = 20;

    /**
     * @param int $page
     * @param int $total
     * @param int $limit
     * @param array $data
     */
    public function pageInfo(int $page, int $total, int $limit, array $data)
    {
        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'successful',
            'pageInfo' => [
                'currentPage' => $page,
                'itemsPerPage' => $limit,
                'totalItems' => $total,
                'hasNext' => ceil($total / $limit) > $page,
                'hasPrevious' => $page > 1,
            ],
            'data' => $data
        ]);
    }

    /**
     * @return int
     */
    public function itemPerPage(): int
    {
        return self::LIMIT;
    }

    /**
     * @param int $page
     * @return int
     */
    public function mapPage(int $page): int
    {
        return $page < 0 ? 1 : $page;
    }
}
