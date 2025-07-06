<?php
namespace App\Repositories;

use App\Models\OrderProduct;

class OrderProductRepository
{
	 /**
     * @var OrderProduct
     */
    protected OrderProduct $orderProduct;

    /**
     * OrderProduct constructor.
     *
     * @param OrderProduct $orderProduct
     */
    public function __construct(OrderProduct $orderProduct)
    {
        $this->orderProduct = $orderProduct;
    }

    /**
     * Get all orderProduct.
     *
     * @return OrderProduct $orderProduct
     */
    public function all()
    {
        return $this->orderProduct->get();
    }

     /**
     * Get orderProduct by id
     *
     * @param $id
     * @return mixed
     */
    public function getById(int $id)
    {
        return $this->orderProduct->find($id);
    }

    /**
     * Save OrderProduct
     *
     * @param $data
     * @return OrderProduct
     */
     public function save(array $data)
    {
        return OrderProduct::create($data);
    }

     /**
     * Update OrderProduct
     *
     * @param $data
     * @return OrderProduct
     */
    public function update(array $data, int $id)
    {
        $orderProduct = $this->orderProduct->find($id);
        $orderProduct->update($data);
        return $orderProduct;
    }

    /**
     * Delete OrderProduct
     *
     * @param $data
     * @return OrderProduct
     */
   	 public function delete(int $id)
    {
        $orderProduct = $this->orderProduct->find($id);
        $orderProduct->delete();
        return $orderProduct;
    }
}
