<?php


namespace arch;


class DataFolderPath
{
    static string $skin;
    static string $geometry;

    static function init(string $dataPath, string $resourcePath) {
        self::$skin = $resourcePath . "skin" . DIRECTORY_SEPARATOR;
        if (!file_exists(self::$skin)) mkdir(self::$skin);

        self::$geometry = $resourcePath . "geometry" . DIRECTORY_SEPARATOR;
        if (!file_exists(self::$geometry)) mkdir(self::$geometry);
    }
}