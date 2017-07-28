<?php
namespace IceAge;

class Exception extends \Exception {
	const NO_ROUTE = 1;
	const HANDLER_NOT_CALLABLE = 2;
}
