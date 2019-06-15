<?php

namespace Tests;

use Closure;

trait DataRules
{
	/**
	 * @param array $rules
	 * @return bool
	 */
	private function assertRules(array $array, array $rules): bool
	{
		foreach ($rules as $key => $rule) {
			$key = explode(".", $key);
			$ptrVal = null;
			if (!$this->internalIndexCheck($array, $key, $ptrVal)) {
				dd($array, $key, $ptrVal);
			}
			if (is_array($rule) && ($rule[1] instanceof Closure)) {
				$this->assertTrue((bool)$rule[1]($ptrVal));
			}
		}
		return true;
	}

	/**
	 * @param array $array
	 * @param array $indexes
	 * @param mixed $ptrVal
	 * @return bool
	 */
	private function internalIndexCheck(array $array, array $indexes, &$ptrVal): bool
	{
		while (isset($indexes[0]) && array_key_exists($indexes[0], $array)) {
			$array = $array[$indexes[0]];
			array_shift($indexes);
		}

		if ($ret = (sizeof($indexes) === 0)) {
			$ptrVal = $array;
		}

		return $ret;
	}
}
