<?php
namespace PandaBlack\Models;
use Plenty\Modules\Plugin\DataBase\Contracts\Model;
class Attributes extends Model
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $category_identifier;

    /**
     * @var int
     */
    public $attribute_identifier;

    /**
     * @var string
     */
    public $name;


    public function getTableName(): string
    {
        return 'PandaBlack::Attributes';
    }
}

