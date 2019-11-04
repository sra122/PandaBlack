<?php
namespace PandaBlack\Models;
use Plenty\Modules\Plugin\DataBase\Contracts\Model;
class Notifications extends Model
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $notification_identifier;

    /**
     * @var string
     */
    public $message;

    /**
     * @var bool
     */
    public $read;

    /**
     * @var int
     */
    public $time;


    public function getTableName(): string
    {
        return 'PandaBlack::Notifications';
    }
}

