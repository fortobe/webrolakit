<?
// _ajax_action - событие на яаксе
// ответ записывам в $this->_set_ajax_response($a_result);

// _action - событие без аякса
// ответ записываем в $this->_set_response($a_result);
// вывод результата action - $NAMECLASS->_response();
namespace wrk\classes;
use \wrk\classes\main as WRK;

class wrk_actions {

	private $s_action = '';

	private $s_type_action;

	private $d_response;

	private $response = [
		'success' => false,
		'message' => 'Произошла неизвестная ошибка',
	];

	const ERROR_INVALID_FIELD = "Это поле содержит ошибку";
	const ERROR_INVALID_EMAIL = "Неверный формат email";
	const ERROR_INVALID_PHONE = "Неверный формат номера";
	const ERROR_EMPTY_FIELD = "Это поле не должно быть пустым";
	const ERROR_FIELD_REQUIRED = "Это обязательное поле";
	const ERROR_SERVER = "Произошла ошибка сервера";
    const ERROR_FIELDS_MESSAGE = "Некоторые поля содержат ошибку";
	const SUCCESS_SENT = "Успешно отправлено!";

	const PHONE_EXP = "/^(\+?[0-9]{1,3})?(\s?\(?[0-9]{2,4}\)?\s?)?([0-9]{2,4}-?\s?){1,3}$/";
	const EMAIL_EXP = "/^[a-zA-Z0-9-\._]+@([-a-z0-9]+\.)+[a-z]{2,4}$/";

	public function __construct() {
		if (isset($_REQUEST["ajax_action"])) {
			$this->s_type_action="_ajax";
			$this->s_action = $_REQUEST["ajax_action"];
		}
		if (isset($_REQUEST["wrk_action"])) {
			$this->s_type_action="";
			$this->s_action = $_REQUEST["wrk_action"];
		}
		$this->_do_action();
	}

	private function _do_action() {
		if (isset($this->s_action) && isset($this->s_type_action)) {
			$s_name = $this->s_type_action."_action_".$this->s_action;
			$this->$s_name();
			if ($this->s_type_action) $this->_set_ajax_response();
		}
	}

	public function _response($s_action) {
		if ($s_action == $this->s_action)
			return $this->d_response;
	}

	private function _set_ajax_response() {
		echo json_encode($this->response);
		die();
	}

	private function _set_response($a_result) {
		$this->d_response = $a_result;
	}

	private function validate_email($s_email) {
		return preg_match(self::EMAIL_EXP, $s_email);
	}

	private function validate_phone($s_phone) {
		return preg_match(self::PHONE_EXP, $s_phone);
	}

	private function validate_required($s_val) {
		return strlen(preg_replace("/\s/", '', $s_val)) > 0;
	}

	private function validate_range($s_val, $i_min = 1, $i_max = 0) {
		$b_valid = strlen($s_val) >= $i_min;
		if ($i_max > 0) $b_valid = $b_valid && strlen($s_val) <= $i_max;
		return $b_valid;
	}

	public function _ajax_action_test(){
		if ($_REQUEST['testmsg']){
			$this->response['status'] = 'success';
			$this->response['message'] = "You've sent: ".$_REQUEST["testmsg"];
		}
	}
	
	public function _ajax_action_useracts() {
		global $USER, $APPLICATION;
		switch($_REQUEST['useract']){
			case "auth": 
				break;
			case "register": 
				break;
			case "recovery":
				break;
			case "edit":
				break;
		}
	}
	
}
?>
