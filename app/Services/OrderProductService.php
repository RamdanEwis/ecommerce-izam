<?php
namespace App\Services;

use App\Models\OrderProduct;
use App\Repositories\OrderProductRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderProductService
{
	/**
     * @var OrderProductRepository $orderProductRepository
     */
    protected $orderProductRepository;

    /**
     * DummyClass constructor.
     *
     * @param OrderProductRepository $orderProductRepository
     */
    public function __construct(OrderProductRepository $orderProductRepository)
    {
        $this->orderProductRepository = $orderProductRepository;
    }

    /**
     * Get all orderProductRepository.
     *
     * @return String
     */
    public function getAll()
    {
        return $this->orderProductRepository->all();
    }

    /**
     * Get orderProductRepository by id.
     *
     * @param $id
     * @return String
     */
    public function getById(int $id)
    {
        return $this->orderProductRepository->getById($id);
    }

    /**
     * Validate orderProductRepository data.
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
    public function save(array $data)
    {
        return $this->orderProductRepository->save($data);
    }

    /**
     * Update orderProductRepository data
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
    public function update(array $data, int $id)
    {
        DB::beginTransaction();
        try {
            $orderProductRepository = $this->orderProductRepository->update($data, $id);
            DB::commit();
            return $orderProductRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw new InvalidArgumentException('Unable to update post data');
        }
    }

    /**
     * Delete orderProductRepository by id.
     *
     * @param $id
     * @return String
     */
    public function deleteById(int $id)
    {
        DB::beginTransaction();
        try {
            $orderProductRepository = $this->orderProductRepository->delete($id);
            DB::commit();
            return $orderProductRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw new InvalidArgumentException('Unable to delete post data');
        }
    }

}
