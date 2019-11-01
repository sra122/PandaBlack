<?php
namespace PandaBlack\Models;
use Plenty\Modules\Plugin\DataBase\Contracts\Model;
class AttributeValues extends Model
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var int
     */
    public $attribute_identifier;

    /**
     * @var int
     */
    public $category_identifier;

    /**
     * @var int
     */
    public $attribute_value_identifier;

    /**
     * @var string
     */
    public $name;


    public function getTableName(): string
    {
        return 'PandaBlack::AttributeValues';
    }
}