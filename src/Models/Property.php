<?php
namespace PandaBlack\Models;
use Plenty\Modules\Plugin\DataBase\Contracts\Model;
class Property extends Model
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $key;

    /**
     * @var string
     */
    public $value;


    public function getTableName(): string
    {
        return 'PandaBlack::Property';
    }
}

