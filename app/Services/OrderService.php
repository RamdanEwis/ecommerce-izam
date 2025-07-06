<?php
namespace App\Services;

use App\Models\Order;
use App\Repositories\OrderRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderService
{
	/**
     * @var OrderRepository $orderRepository
     */
    protected $orderRepository;

    /**
     * DummyClass constructor.
     *
     * @param OrderRepository $orderRepository
     */
    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Get all orderRepository.
     *
     * @return String
     */
    public function getAll()
    {
        return $this->orderRepository->all();
    }

    /**
     * Get orderRepository by id.
     *
     * @param $id
     * @return String
     */
    public function getById(int $id)
    {
        return $this->orderRepository->getById($id);
    }

    /**
     * Validate orderRepository data.
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
    public function save(array $data)
    {
        return $this->orderRepository->save($data);
    }

    /**
     * Update orderRepository data
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
    public function update(array $data, int $id)
    {
        DB::beginTransaction();
        try {
            $orderRepository = $this->orderRepository->update($data, $id);
            DB::commit();
            return $orderRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw new InvalidArgumentException('Unable to update post data');
        }
    }

    /**
     * Delete orderRepository by id.
     *
     * @param $id
     * @return String
     */
    public function deleteById(int $id)
    {
        DB::beginTransaction();
        try {
            $orderRepository = $this->orderRepository->delete($id);
            DB::commit();
            return $orderRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw new InvalidArgumentException('Unable to delete post data');
        }
    }

}
