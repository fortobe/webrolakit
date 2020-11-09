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
	const ERROR_MUST_CHECKED = "Подтвердите свое согласие";
	const ERROR_FIELD_REQUIRED = "Это обязательное поле";
	const ERROR_SERVER = "Произошла ошибка сервера";
	const ERROR_FIELDS_MESSAGE = "Некоторые поля содержат ошибку";
	const VALIDATION_ERROR = "Произошла ошибка валидации. Попробуйте перезагрузить страницу и попробовать ещё раз.";
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

	private function validate_captcha($token = 'g-recaptcha-response') {
		if (isset($_REQUEST[$token])) {
			if (!empty($_REQUEST[$token])) {
				$url = 'https://www.google.com/recaptcha/api/siteverify';
				$data = array('secret' => GC_SECRET_KEY, 'response' => $_REQUEST[$token]);
				$options = [
					'http' => [
						'header' => "Content-type: application/x-www-form-urlencoded\r\n",
						'method' => "POST",
						"content" => http_build_query($data),
					],
				];
				$context = stream_context_create($options);
				$response = file_get_contents($url, false, $context);
				$responseData = json_decode($response, true);
				if (!$responseData['success'] && $responseData['score'] > .8) {
					$this->response['errors']['captcha'] = '';
					$this->response['message'] = self::VALIDATION_ERROR;
				}
				$this->response['debug']['captcha']['context'] = get_resource_type($context);
				$this->response['debug']['captcha']['response'] = $response;
				$this->response['debug']['captcha']['responseData'] = $responseData;
			} else {
				$this->response['errors']['captcha'] = '';
				$this->response['message'] = self::VALIDATION_ERROR;
				$this->response['debug']['captcha'][] = 'empty token';
			}
		} elseif (isset($_REQUEST['agree']) && !empty($_REQUEST['agree'])) {
			$this->response['errors']['valid'] = '';
			$this->response['message'] = self::VALIDATION_ERROR;
		}
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

	public function _action_store_city() {
		$_SESSION['STORES_CITY'] = $_POST['city'];
	}

	private function _ajax_action_feedback() {
		if (empty($_POST['policy_check'])) {
			$this->response['errors']['policy_check'] = self::ERROR_MUST_CHECKED;
		}

		$this->validate_captcha();

		if (!$this->validate_required($_POST['name'])) {
			$this->response['errors']['name'] = self::ERROR_EMPTY_FIELD;
		}
		if (!$this->validate_phone($_POST['phone'])) {
			$this->response['errors']['phone'] = self::ERROR_INVALID_PHONE;
		}
		if (!in_array($_POST['event'], [/*TODO Define the EVENTS list*/])) {
			$this->response['errors']['event'] = self::ERROR_SERVER;
		}
		if (in_array($_POST['event'], [/*TODO Define the EVENTS list*/]) && !$this->validate_required($_POST['city'])) {
			$this->response['errors']['city'] = self::ERROR_FIELD_REQUIRED;
		}
		if (in_array($_POST['event'], [/*TODO Define the EVENTS list*/]) && !$this->validate_required($_POST['store'])) {
			$this->response['errors']['store'] = self::ERROR_FIELD_REQUIRED;
		}
		if (isset($_POST['email']) && !$this->validate_email($_POST['email'])) {
			$this->response['errors']['email'] = self::ERROR_INVALID_EMAIL;
		}
		if (empty($this->response['errors'])) {
			$fields = [
				'NAME' => $_POST['name'],
				'PHONE' => $_POST['phone'],
				'EMAIL' => $_POST['email']?:'(не указан)',
				'ADDITIONAL' => $_POST['additional']?:'(без комментария)',
				'DATE' => $_POST['date']?date('d.m.Y',strtotime($_POST['date'])):'(Не указано)',
				'TIME' => $_POST['time']?:'(Не указано)',
				'EMAIL_TO' => \COption::GetOptionString('main', 'email_from'),
			];
			/*section CITY*/
			if ($_POST['event'] !== 'CALLBACK') {
				$fields['CITY'] = WRK::get_hlblock_entries('Cities', [
					'filter' => [
						'UF_XML_ID' => $_POST['city'],
					],
					'only' => 1,
				])['UF_NAME'];
				$arStore = WRK::get_iblock_elems([
					'id' => $_POST['store'],
				], true);
				$fields['STORE'] = $arStore['NAME'];
				$fields['EMAIL_TO'] = $arStore['PROPS']['EMAIL']['VALUE']?:$fields['EMAIL_TO'];
			}
			if(!empty($_FILES['files']['name'][0])) {
				$fileIDs = [];
				foreach (organise_files_array($_FILES['files']) as $arFile) {
					$fileIDs[] = \CFile::SaveFile(array_merge($arFile, [
						'del' => 'Y',
						'MODULE_ID' => 'main',
					]), '/tmp/emails/');
				}
			}
			\CEvent::SendImmediate($_POST['event'], ['s1', 'ru'], $fields, 'Y', '', $fileIDs ?: []);
			$this->response['message'] = self::SUCCESS_SENT;
			$this->response['success'] = true;
			if (!!$fileIDs) {
				foreach ($fileIDs as $id) {
					\CFile::Delete($id);
				}
			}
		} else $this->response['message'] = self::ERROR_FIELDS_MESSAGE;
	}

	private function _ajax_action_cookie_accept() {
		setcookie('ACCEPT_COOKIE', '1', time() + (3600 * 14));
	}
}
?>
