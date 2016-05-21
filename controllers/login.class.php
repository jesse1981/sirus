<?php
class login extends template {
	public function index() {
		$this->setView('login','_master.php');
	}
	public function invalid() {
		$this->setView('login','_master.php');
	}
	public function logout() {
		$dash = new dashboard();
		$user = new user();
		$user->logout();
		$dash->redirect('index/index');
	}
}
?>