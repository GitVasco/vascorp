<?php

class Conexion
{
	private static $link = null;

	static public function conectar()
	{
		if (self::$link instanceof PDO) {
			return self::$link;
		}

		self::$link = new PDO(
			"mysql:host=192.168.1.64;dbname=vasco",
			"admin",
			"joel123"
		);

		self::$link->exec("set names utf8");

		return self::$link;
	}
}
