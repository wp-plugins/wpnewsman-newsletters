<?php

require_once('class.options.php');
require_once('class.list.php');

class newsmanForm {

	var $decodedForm;
	var $adminMode;
	var $useInlineLabels;

	public function __construct($id, $admin = false) {
		$o = newsmanOptions::getInstance();

		if ( is_numeric($id) ) { // id passed
			$list = newsmanList::findOne('id = %d', array($id));	
		} else { // uid passed
			$list = newsmanList::findOne('uid = %s', array($id));
		}

		$this->list = $list;

		// form onbject contains form elements,
		// and general form options like useInlineLabels
		$formObj = json_decode($list->form, true);

		$this->useInlineLabels = $formObj['useInlineLabels'];	
		$this->decodedForm = $formObj['elements'];

		// echo '<pre>';
		// print_r($list->form);
		// echo '</pre>';

		$this->uid = $list->uid;

		// make sure submit button is present in form
		
		if ( is_array($this->decodedForm) ) {
			$hasSubmit = false;
			foreach ($this->decodedForm as $item) {
				if ( $item['type'] === 'submit' ) {
					$hasSubmit = true;
				}
			}			
			if ( !$hasSubmit ) {
				$this->decodedForm[] = array(
					'type' => 'submit',
					'name' => 'nwsmn-subscribe',
					'value' => __('Subscribe', NEWSMAN)
				);
			}			
		}

		$this->adminMode = $admin;
	}

	private function getCloseButton() {		
		return $this->adminMode ? '<button class="close">&times;</button>' : '';
	}

	private function getText($item) {
		$req = isset($item['required']) && $item['required'] ? 'newsman-required' : '';
		$type = $this->adminMode ? 'gstype="text"' : '';
		$it = $item['type'];

		$lblSt = $this->useInlineLabels ? 'style="display: none;"' : '';

		$lbl = '<label '.$lblSt.' >'.$item['label'].'</label>';
		$ph = $this->useInlineLabels ? 'placeholder="'.$item['label'].'"' : '';

		return	"<li $type class=\"newsman-form-item $req $it\">".
					$lbl.
					'<input type="text" name="'.$item['name'].'" value="'.$item['value'].'" '.$ph.'>'.
					'<span class="newsman-required-msg" style="display:none;">'.__('Required', NEWSMAN).'</span>'.
					$this->getCloseButton().
				'</li>';
	}

	private function getEmail($item) {
		$req = isset($item['required']) && $item['required'] ? 'newsman-required' : '';
		$type = $this->adminMode ? 'gstype="email"' : '';
		$it = $item['type'];

		$lblSt = $this->useInlineLabels ? 'style="display: none;"' : '';

		$lbl = '<label '.$lblSt.'>'.$item['label'].'</label>';
		$ph = $this->useInlineLabels ? 'placeholder="'.$item['label'].'"' : '';

		return 	"<li $type class=\"newsman-form-item $req $it\">".
					$lbl.
					'<input type="text" name="newsman-email" '.$ph.' value="'.$item['value'].'">'.
					'<span class="newsman-required-msg" style="display:none;">'.__('Required', NEWSMAN).'</span>'.
				'</li>';
	}	

	private function getCheckbox($item) {
		$req = isset($item['required']) && $item['required'] ? 'newsman-required' : '';
		$type = $this->adminMode ? 'gstype="checkbox"' : '';
		$chkd = $item['checked'] ? 'checked="checked"' : '';
		$it = $item['type'];
		return "<li $type class=\"newsman-form-item $req $it\">".
					'<label class="checkbox">'.
						'<input type="checkbox" '.$chkd.' name="'.$item['name'].'" value="'.$item['value'].'"> '.
						$item['label'].
					'</label>'.
					'<span style="display:none" class="newsman-required-msg cbox">'.__('Required', NEWSMAN).'</span>'.
					$this->getCloseButton().
				'</li>';
	}

	private function valueFromLabel($lbl) {
		return preg_replace('#\W+#i', '-', $lbl);
	}

	private function getRadio($item) {
		$req = isset($item['required']) && $item['required'] ? 'newsman-required' : '';
		$type = $this->adminMode ? 'gstype="radio"' : '';
		$it = $item['type'];
		$radios = '';
		$children = isset($item['value']) ? $item['value'] : $item['children'];
		$i = 0;
		foreach ($children as $radio) {

			$i+=1;
			$id = "rad-".$i;			

			$val = isset($radio['value']) ? $radio['value'] : $this->valueFromLabel($radio['label']);
			$chkd = $radio['checked'] ? 'checked="checked"' : '';
			$radios .= 	'<label id="'.$id.'" class="radio">'.
							'<input type="radio" name="'.$item['name'].'" '.$chkd.' value="'.$val.'">'.
							'<span>'.$radio['label'].'</span>'.
						'</label>';
		}
		return "<li $type class=\"newsman-form-item $req $it\">".
					'<label>'.$item['label'].'</label>'.
					'<span style="display:none;" class="newsman-required-msg radio">'.__('Required', NEWSMAN).'</span>'.
					'<div class="newsman-radio-options">'.
					$radios.
					'</div>'.
					$this->getCloseButton().
				'</li>';
	}

	private function getSubmit($item) {
		$type = $this->adminMode ? 'gstype="submit"' : '';
		return '<li '.$type.' class="newsman-form-item">'.
					'<input type="submit" class="btn" name="nwsmn-subscribe" value="'.$item['value'].'">'.
				'</li>';
	}

	public function parse() {
		$parsed = array();
		foreach ($this->decodedForm as $item) {
			$n = $item['name'];
			if ( isset($_POST[$n]) ) {
				$parsed[$n] = $_POST[$n];
			}
		}
		$parsed['email'] = $_POST['newsman-email'];
		return $parsed;
	}

	public function getForm($use_excerpts = false) {
		if ( !is_array($this->decodedForm) ) {
			echo '<p class="error">The form settings are corrupted.</p>';
			return;
		}
		$il = $this->useInlineLabels ? ' inline-labels' : '';
		$formHtml = '<ul class="newsman-form'.$il.'">';
		$hasEmailField = false;

		foreach ($this->decodedForm as $item) {

			if ( $item['type'] === 'email' ) {
				$hasEmailField = true;
				$item['required'] = true;
			}

			$method = 'get'.ucfirst($item['type']);			
			$renderedItem = '';
			if ( method_exists($this, $method) ) {
				$renderedItem = call_user_method($method, $this, $item);
			}
			$formHtml .= $renderedItem;
		}

		if ( !$hasEmailField ) {
			$formHtml .= $this->getEmail(array(
				'label' => 'Email:',
				'value' => ''
			));
		}

		$formHtml .= '<input type="hidden" name="uid" value="'.$this->uid.'">';

		if ( $use_excerpts ) {
			$formHtml .= '<input type="hidden" name="newsman_use_excerpts" value="1">';
		}

		return $formHtml.'</ul>';
	}

	public function getFields() {
		$fields = array();
		foreach ($this->decodedForm as $item) {
			$fields[$item['name']] = $item['label'];
		}
		return $fields;
	}

	public function renderForm() {
		echo $this->getForm();
	}

}