<?php

namespace PandaBlack\Models;

use Plenty\Modules\Plugin\DataBase\Contracts\Model;

class PbCategories extends Model
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $categoryId;

    /**
     * @var string
     */
    public $treePath;
}