<?php
namespace PandaBlack\Models;
use Plenty\Modules\Plugin\DataBase\Contracts\Model;
class Properties extends Model
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
    public $value;


    public function getTableName(): string
    {
        return 'PandaBlack::Properties';
    }
}

