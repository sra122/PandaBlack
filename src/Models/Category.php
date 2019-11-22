<?php
namespace PandaBlack\Models;
use Plenty\Modules\Plugin\DataBase\Contracts\Model;
class Category extends Model
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
     * @var string
     */
    public $tree_path;


    public function getTableName(): string
    {
        return 'PandaBlack::Categories';
    }
}

