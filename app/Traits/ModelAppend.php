<?php

namespace App\Traits;

trait ModelAppend
{

    private static $additionalAppends = [];
    private static $removeableAppends = [];
    private static $removeAllAppends = false;
    private static $visibleAppends = [];

    public static function addAppends($appends = [])
    {
        foreach ($appends as $obj) {
            array_push(self::$additionalAppends, $obj);
        }
    }

    public static function removeAppends($appends = [])
    {
        self::$removeableAppends = $appends;
    }

    public static function removeAllAppends()
    {
        self::$removeAllAppends = true;
    }

    protected function getArrayableAppends()
    {
        $appends = parent::getArrayableAppends();
        if (self::$removeAllAppends)
            $appends = [];

        foreach (self::$additionalAppends as $obj) {
            array_push($appends, $obj);
        }
        $appends = array_diff($appends, self::$removeableAppends);
        return $appends;
    }

    public static function addVisibility($appends = [])
    {
        foreach ($appends as $obj) {
            array_push(self::$visibleAppends, $obj);
        }
    }

    public function toArray()
    {
        $this->setAttributeVisibility();
        return parent::toArray();
    }

    public function setAttributeVisibility()
    {
        $this->makeVisible(array_merge($this->fillable, $this->appends, self::$visibleAppends));
    }
}
