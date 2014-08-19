<?php

/**
 * This is the model class for tablas Padre hijo
 * La clase descendiente utiliza el metodo pasarDatosPadre para inicializar atributos 
 * que dependen de la clase padre. 
 */
class PHActiveRecord extends CActiveRecord
{
	/**
	 * 
	 * Se activa cuando este baja ya nos se va utilizar mas
	 * Ver de {@link CursoController} el procedimientos de actualización. 
	 * @var boolean indica que es un CActivoRecord a darse de baja.
	 */
	public $baja = false;
	
	/**
	 * 
	 * En modelHijos guardamos los modelos generados tras editar un formulario.
	 * @var array $modelHijos
	 */
	public $modelHijos = array();
	/**
	 * Metodo que captura la asignación a $modelHijos 
	 * En modelHijos guardamos los modelos generados via formulario.
	 * @param Object $modelHijos descendiente de CActiveRecord
	 */
	public function setModelHijos($modelHijos) {
		$this->modelHijos[] = $modelHijos;
	}
	/**
	 * 
	 * Para los descendientes que no quieren utilizar el validate de PH
	 * y quieren acceder directamente al validate del CActiveRecord;
	 * @param string/array $attributes
	 * @param boolean $clearErrors
	 */
	public function validateCAR($attributes=null, $clearErrors=true) {
		return parent::validate($attributes,$clearErrors);
	}
	public function validate($attributes=null, $clearErrors=true) {
		$val = parent::validate($attributes,$clearErrors);
		foreach($this->modelHijos as $modelhijo) {
			// los marcados como baja NO se han de validar
			if ($modelhijo->baja === true) continue;
			// false por que en el validate del curso ya se validan fallos en los periodos lectivos..
			if (! $modelhijo->validate() ) $val = false;
		}
		return $val;
	}
	/**
	 * 
	 * Utilizarlo en el caso de que el Padre ya marca errores en el hijo
	 * y por lo tanto el hijo no ha de limpiarse antes de procesar.
	 * @param unknown_type $attributes
	 * @param unknown_type $clearErrors
	 */
	public function validateIntegrado($attributes=null, $clearErrors=true) {
		//Necesario limpiar ahora errores de una pasada anterior, ya que despues no se puede hacer
		foreach($this->modelHijos as $modelhijo) {
			$modelhijo->clearErrors();
		}
		$val = parent::validate($attributes,$clearErrors);
		foreach($this->modelHijos as $modelhijo) {
			// los marcados como baja NO se han de validar
			if ($modelhijo->baja === true) continue;
			// false por que en el validate del curso ya se validan fallos en los periodos lectivos..
			if (! $modelhijo->validate(Null,false) ) $val = false;
		}
		return $val;		
		
	}	
	public function update($attributes=null) {
		$val = parent::update($attributes);
		
		if ($val) {
			$val2 = $this->acthijos();
			if (!$val2) $val = false;
		}
		
		return $val;
	}
	public function insert($attributes=null) {
		$val = parent::insert($attributes);
		if ($val) {
			$val2 = $this->acthijos();
			if (!$val2) $val = false;
		}
		
		return $val;
	}
	/**
	 * 
	 * Para los descendientes que no quieren utilizar el delete de PH
	 * y quieren acceder directamente al delete del CActiveRecord;
	 */
	
	public function deleteCAR() {
		return parent::delete();
	}
	public function delete() {
		$this->baja = true;
		$val = $this->acthijos();
		if ($val) {
			$val = parent::delete();
		}
		return $val;
	}
	private function acthijos() {
		$val = true;
		$val2 = true;
		$this->beforeActhijos();
		foreach($this->modelHijos as $modelhijo) {
			if ($modelhijo->getIsNewRecord()) {
				$this->pasarDatosPadre($modelhijo);
				$val2 = $modelhijo->insert();
			} else {
				if ($this->baja || $modelhijo->baja ) $val2 = $modelhijo->delete();
				else $val2 = $modelhijo->update();
			}
			if (!$val2) $val = false;
		}
		return $val;
	}
	
	/**
	* Definir nuevos eventos para ser capturados 
	* antes de actualizar a la base de datos los hijos
	*
	*/
	public function onBeforeActhijos($event) {
	    $this->raiseEvent('onBeforeActhijos',$event);
	}
	protected function beforeActhijos() {
	    if($this->hasEventHandler('onBeforeActhijos'))
	        $this->onBeforeActhijos(new CEvent($this));
	}
	/**
	* Definir nuevos eventos para ser capturados 
	* despues de actualizar a la base de datos los hijos
	*
	*/
	public function onAfterActhijos($event) {
	    $this->raiseEvent('onAfterActhijos',$event);
	}
	/**
	 * 
	 * Llamado despues des pues que act hijos procesara el bucle de hijos.
	 * @param boolean $val si ha ido bien o mal la actualización de hijos.
	 */
	protected function afterActhijos($val) {
	    if($this->hasEventHandler('onAfterActhijos'))
	        $this->onAfterActhijos(new CEvent($this));
	}	
	
	/**
	 * 
	 * La clase descendiente Padre a de actualizar con los datos
	 * que necesita el modelohijo que se esta procesando.
	 * @param object $modelhijo
	 */
	protected function pasarDatosPadre($modelhijo) {
		
	}
	
}