<?php

class newsmanForm {

	var $decodedForm;
	var $adminMode;
	var $useInlineLabels;
	var $horizontal;

	var $hpCSSClassName;

	var $adminSubmissionMode = false;

	var $elId;

	public function __construct($id, $admin = false) {
		$o = newsmanOptions::getInstance();

		if ( is_numeric($id) ) { // id passed
			$list = newsmanList::findOne('id = %d', array($id));	
		} else { // uid passed
			$list = newsmanList::findOne('uid = %s', array($id));
		}

		$this->horizontal = false;

		$this->title = '';

		$this->list = $list;

		// form onbject contains form elements,
		// and general form options like useInlineLabels
		$formObj = json_decode($list->form, true);

		$this->raw = $list->form;

		$this->useInlineLabels = $this->adminSubmissionMode ? false : $formObj['useInlineLabels'];	
		$this->decodedForm = $formObj['elements'];

		$this->elId = 0;

		$this->uid = $list->uid;

		// make sure submit button is present in form
		
		if ( is_array($this->decodedForm) ) {
			$hasSubmit = false;
			foreach ($this->decodedForm as $item) {
				if ( $item['type'] === 'submit' ) {
					$hasSubmit = true;
				} elseif ( $item['type'] === 'title' ) {
					$this->title = $item['value'];
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

		$this->hpCSSClassName = $this->randStr(15);
	}

	private function randStr($len = 8) {
		return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $len);
	}

	private function getHPCSS() {
		return '
			<style>
				.'.$this->hpCSSClassName.' {
					position: absolute;
					left: -9999px;
					top: -9999px;
				}
			</style>
		';
	}

	private function ent($str) {
		return htmlentities($str, ENT_COMPAT, 'UTF-8');
	}

	private function specChr($str) {
		return htmlspecialchars($str, ENT_COMPAT, 'UTF-8');
	}

	private function getElId() {
		$this->elId += 1;

		return 'newsman-form-el-'.$this->elId;
	}

	private function getCloseButton() {		
		return $this->adminMode ? '<button class="close">&times;</button>' : '';
	}

	private function getHPField() {
		if ( $this->adminSubmissionMode ) { return ''; } 
		$lblSt = $this->useInlineLabels ? 'style="display: none;"' : '';

		$formFiledNames = array();
		foreach ($this->decodedForm as $item) {
			if ( isset($item['name']) ) {
				$formFiledNames[] = $item['name'];
			}			
		}		

		$fieldNames = array('url', 'title', 'description', 'country');
		$fn = $this->randStr();

		foreach ($fieldNames as $fieldName) {
			if ( !in_array($fieldName, $formFiledNames) ) {
				$fn = $fieldName;
				break;
			}
		}

		$label = ucfirst($fn);

		$lbl = '<label class="newsman-form-item-label" '.$lblSt.' >'.$this->ent($label).'</label>';
		$ph = $this->useInlineLabels ? 'placeholder="'.$this->specChr($label).'"' : '';

		$elId = $this->getElId();

		return	"<div style=\"display: none;\" class=\"newsman-form-item ".$this->hpCSSClassName." newsman-form-item-text $elId\">".
					$lbl.
					'<input type="text" name="'.$this->specChr($fn).'" value="" '.$ph.'>'.
					'<input type="hidden" name="newsman-special-h" value="'.$this->specChr($fn).'">'.
					'<span class="newsman-required-msg" style="display:none;">'.__('Required', NEWSMAN).'</span>'.
				'</div>';		
	}

	private function validateHPFields() {
		if ( $this->adminSubmissionMode ) { return true; } 
		$u = newsmanUtils::getInstance();
		$o = newsmanOptions::getInstance();

		$hpFieldName = $_REQUEST['newsman-special-h'];
		if ( !$hpFieldName ) { return false; }

		$hpFieldValue = $_REQUEST[$hpFieldName];
		if ( $hpFieldValue !== '' ) { return false; }

		return true;
	}

	private function getText($item) {

		$req = isset($item['required']) && $item['required'] ? 'newsman-required' : '';
		$type = $this->adminMode ? 'gstype="text"' : '';
		$it = 'newsman-form-item-'.$item['type'];

		$lblSt = $this->useInlineLabels ? 'style="display: none;"' : '';

		$lbl = '<label class="newsman-form-item-label" '.$lblSt.' >'.$this->ent($item['label']).'</label>';
		$ph = $this->useInlineLabels ? 'placeholder="'.$this->specChr($item['label']).'"' : '';

		$elId = $this->getElId();

		return	"<div $type class=\"newsman-form-item $req $it $elId\">".
					$lbl.
					'<input type="text" name="'.$this->specChr($item['name']).'" value="'.$this->specChr($item['value']).'" '.$ph.'>'.
					'<span class="newsman-required-msg" style="display:none;">'.__('Required', NEWSMAN).'</span>'.
					$this->getCloseButton().
				'</div>';
	}

	private function getTextarea($item) {

		$req = isset($item['required']) && $item['required'] ? 'newsman-required' : '';
		$type = $this->adminMode ? 'gstype="textarea"' : '';
		$it = 'newsman-form-item-'.$item['type'];

		$lblSt = $this->useInlineLabels ? 'style="display: none;"' : '';

		$lbl = '<label class="newsman-form-item-label" '.$lblSt.' >'.$this->ent($item['label']).'</label>';
		$ph = $this->useInlineLabels ? 'placeholder="'.$this->specChr($item['label']).'"' : '';

		$elId = $this->getElId();

		return	"<div $type class=\"newsman-form-item $req $it $elId\">".
					$lbl.
					'<textarea name="'.$this->specChr($item['name']).'" '.$ph.'>'.$this->specChr($item['value']).'</textarea>'.
					'<span class="newsman-required-msg" style="display:none;">'.__('Required', NEWSMAN).'</span>'.
					$this->getCloseButton().
				'</div>';
	}

	private function getEmail($item) {
		$req = isset($item['required']) && $item['required'] ? 'newsman-required' : '';
		$type = $this->adminMode ? 'gstype="email"' : '';
		$it = 'newsman-form-item-'.$item['type'];

		$lblSt = $this->useInlineLabels ? 'style="display: none;"' : '';

		$lbl = '<label class="newsman-form-item-label" '.$lblSt.'>'.$this->ent($item['label']).'</label>';
		$ph = $this->useInlineLabels ? 'placeholder="'.$this->specChr($item['label']).'"' : '';

		$elId = $this->getElId();

		return 	"<div $type class=\"newsman-form-item $req $it $elId\">".
					$lbl.
					'<input type="email" name="newsman-email" '.$ph.' value="'.$this->specChr($item['value']).'">'.
					'<span class="newsman-required-msg" style="display:none;">'.__('Required', NEWSMAN).'</span>'.
				'</div>';
	}	

	private function getCheckbox($item) {
		$req = isset($item['required']) && $item['required'] ? 'newsman-required' : '';
		$type = $this->adminMode ? 'gstype="checkbox"' : '';
		$chkd = $item['checked'] ? 'checked="checked"' : '';
		$it = 'newsman-form-item-'.$item['type'];

		$elId = $this->getElId();

		return "<div $type class=\"newsman-form-item $req $it $elId\">".
					'<label class="checkbox newsman-form-item-label">'.
						'<input type="checkbox" '.$chkd.' name="'.$this->specChr($item['name']).'" value="'.$this->specChr($item['value']).'"> '.
						$this->ent($item['label']).
					'</label>'.
					'<span style="display:none" class="newsman-required-msg cbox">'.__('Required', NEWSMAN).'</span>'.
					$this->getCloseButton().
				'</div>';
	}

	private function valueFromLabel($lbl) {
		return preg_replace('#\W+#i', '-', $lbl);
	}

	private function getRadio($item) {
		$req = isset($item['required']) && $item['required'] ? 'newsman-required' : '';
		$type = $this->adminMode ? 'gstype="radio"' : '';
		$it = 'newsman-form-item-'.$item['type'];
		$radios = '';
		$children = isset($item['value']) ? $item['value'] : $item['children'];
		$i = 0;
		foreach ($children as $radio) {

			$i+=1;
			$id = "rad-".$i;			

			$val = isset($radio['value']) ? $radio['value'] : $this->valueFromLabel($radio['label']);
			$chkd = (isset($radio['checked']) && $radio['checked']) ? 'checked="checked"' : '';
			$radios .= 	'<label id="'.$this->specChr($id).'" class="radio">'.
							'<input type="radio" name="'.$this->specChr($item['name']).'" '.$chkd.' value="'.$this->specChr($val).'">'.
							'<span>'.$this->ent($radio['label']).'</span>'.
						'</label>';
		}

		$elId = $this->getElId();

		return "<div $type class=\"newsman-form-item $req $it $elId\">".
					'<label class="newsman-form-item-label">'.$item['label'].'</label>'.
					'<span style="display:none;" class="newsman-required-msg radio">'.__('Required', NEWSMAN).'</span>'.
					'<div class="newsman-radio-options">'.
					$radios.
					'</div>'.
					$this->getCloseButton().
				'</div>';
	}

	private function getSelect($item) {
		$req = isset($item['required']) && $item['required'] ? 'newsman-required' : '';
		$type = $this->adminMode ? 'gstype="select"' : '';
		$it = 'newsman-form-item-'.$item['type'];
		$options = '';
		$children = isset($item['value']) ? $item['value'] : $item['children'];

		if ( $this->useInlineLabels ) {
			$options .= '<option value="">'.$item['label'].'</option>';
		}

		foreach ($children as $opt) {
			$val = isset($opt['value']) ? $opt['value'] : $this->valueFromLabel($opt['label']);
			//$chkd = (isset($opt['checked']) && $opt['checked']) ? 'checked="checked"' : '';
			$options .= '<option value="'.$this->specChr($opt['value']).'">'.$opt['label'].'</option>';
		}

		$lblSt = $this->useInlineLabels ? 'style="display: none;"' : '';

		$elId = $this->getElId();

		return "<div $type class=\"newsman-form-item $req $it $elId\">".
					'<label '.$lblSt.' class="newsman-form-item-label">'.$item['label'].'</label>'.
					'<span style="display:none;" class="newsman-required-msg radio">'.__('Required', NEWSMAN).'</span>'.
					'<select name="'.$this->specChr($item['name']).'">'.
					$options.
					'</select>'.
					$this->getCloseButton().
				'</div>';
	}	

	private function getHTML($item) {
		if ( $this->adminSubmissionMode ) { return ''; } 
		$it = 'newsman-form-item-'.$item['type'];

		$elId = $this->getElId();

		return "<div class=\"newsman-form-item $it $elId\">".$item['value'].'</div>';
	}

	private function getSubmit($item) {
		if ( $this->adminSubmissionMode ) { return ''; } 
		$elType = $this->adminMode ? 'gstype="submit"' : '';

		$style = isset($item['style']) ? $item['style'] : 'none';
		$size = isset($item['size']) ? $item['size'] : 'small';
		$color = isset($item['color']) ? $item['color'] : 'gray';

		$btnClasses = '';

		$btn = '';

		if ( $style !== 'none' ) {
			$btnClasses .= ' newsman-button';

			$btnClasses .= ' newsman-button-'.$size;
			$btnClasses .= ' newsman-button-'.$style;
			$btnClasses .= ' newsman-button-'.$color;	

			$btn = '<button type="submit" class="'.$btnClasses.'" name="nwsmn-subscribe" value="1">'.$this->ent($item['value']).'</button>';

		} else {
			$btn = '<input type="submit" class="newsman-button-default button btn" name="nwsmn-subscribe" value="'.$this->specChr($item['value']).'">';
		}

		$elId = $this->getElId();

		return "<div ".$elType.' class="newsman-form-item '.$elId.'">'.$btn.'</div>';
	}

	public function parse() {
		if ( !$this->validateHPFields() ) {
			wp_die( __('Spam submission detected. Please contact the site administrator and describe the problem.', NEWSMAN), __('Error', NEWSMAN) );
		}
		$parsed = array();
		foreach ($this->decodedForm as $item) {
			if ( isset($item['name']) ) {
				$n = $item['name'];
				if ( isset($_POST[$n]) ) {
					$parsed[$n] = stripslashes($_POST[$n]);
				}				
			}
		}

		$parsed['email'] = trim(stripslashes($_POST['newsman-email']));
		return $parsed;
	}

	public function getForm($use_excerpts = false) {
		global $post;		

		if ( !is_array($this->decodedForm) ) {
			return '<p class="error">The form settings are corrupted.</p>';
		}
		$il = $this->useInlineLabels ? ' inline-labels' : '';
		$hor = $this->horizontal ? ' newsman-form-horizontal' : '';
		$formHtml = $this->getHPCSS().'<div class="newsman-form'.$il.$hor.'">';
		$hasEmailField = false;

		foreach ($this->decodedForm as $item) {

			if ( $item['type'] === 'email' ) {
				$hasEmailField = true;
				$item['required'] = true;
			}

			$method = 'get'.ucfirst($item['type']);			
			$renderedItem = '';
			if ( method_exists($this, $method) ) {
				$renderedItem = call_user_func( array($this, $method), $item);
			}
			$formHtml .= $renderedItem;			
		}

		$formHtml .= $this->getHPField();

		if ( !$hasEmailField ) {
			$formHtml .= $this->getEmail(array(
				'label' => 'Email:',
				'value' => ''
			));
		}

		$formHtml .= '<input type="hidden" name="uid" value="'.$this->specChr($this->uid).'">';
		$formHtml .= '<input class="newsman-form-url" type="hidden" name="newsman-form-url" value="'.$_SERVER['REQUEST_URI'].'">';

		if ( defined('ICL_LANGUAGE_CODE') ) {
			$formHtml .= '<input type="hidden" name="_form_lang" value="'.ICL_LANGUAGE_CODE.'">';
		}

		if ( $use_excerpts ) {
			$formHtml .= '<input type="hidden" name="newsman_use_excerpts" value="1">';
		}

		return $formHtml.'</div>';
	}

	public function getFields() {
		$u = newsmanUtils::getInstance();
		$fields = array();

		foreach ($this->decodedForm as $item) {

			if ( isset($item['name']) && isset($item['type']) && $item['type'] !== 'html' ) {
				$key = ( $item['type'] === 'email' ) ? 'email' : $item['name'];
				$fields[$key] = $item['label'];
			}			
		}
		return $fields;
	}

	public function renderForm() {
		echo $this->getForm();
	}

}