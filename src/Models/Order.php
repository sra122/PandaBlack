<?php
namespace PandaBlack\Models;
use Plenty\Modules\Plugin\DataBase\Contracts\Model;
class Order extends Model
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $reference_key;

    /**
     * @var int
     */
    public $order_id;


    public function getTableName(): string
    {
        return 'PandaBlack::Orders';
    }
}

