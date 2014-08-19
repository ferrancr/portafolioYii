<?php

/**
 * This is the model class for table "gi_usuario".
 *
 * The followings are the available columns in table 'gi_usuario':
 * @property integer $id
 * @property string $nombre
 * @property string $fecha_creacion
 * @property string $estado
 * @property integer $id_especialidad
 * @property string $email
 *
 * The followings are the available model relations:
 * @property Alumno[] $alumnos
 * @property Login[] $login
 * @property Profesor[] $profesores
 * @property Usuficha $usuficha
 */
class Usuario extends PHActiveRecord
{
	
	/**
	 * Returns the static model of the specified AR class.
	 * @return Usuario the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'gi_usuario';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('nombre,estado', 'required'),
			array('id_especialidad', 'numerical', 'integerOnly'=>true,'allowEmpty'=>true),
			array('nombre', 'length', 'max'=>256),
			array('estado','in', 'range'=> self::getEstados(NULL,true)),
			array('email', 'length', 'max'=>128),
			array('email', 'CEmailValidator'),
			array('email', 'unique', 'message' => 'La direccion de correo ya la tiene otro usuario'),			
			array('id_especialidad','DatosMValidator','tipo'=>'especialidad'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, nombre, fecha_creacion, estado,tipo, id_especialidad, email', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		// FIXME: FACER Verificar si el HAS_MANY de logins puede llegar a tener sentido.
		return array(
			'alumnos' => array(self::HAS_MANY, 'Alumno', 'id_usuario'),
			'login' => array(self::HAS_ONE, 'Login', 'id_usuario'),
			'profesores' => array(self::HAS_MANY, 'Profesor', 'id_usuario'),
			'usuficha' => array(self::HAS_ONE, 'Usuficha', 'id_usuario'),
			'asistencias' => array(self::HAS_MANY, 'Asistencia', 'id_usuario'),
		);
	}
	/**
	 * Se añade el comportamiento de incluir la fecha y usuario que creó el usuario.
	 * @return array behaviors.
	 */
	public function behaviors() {
		return array(
			'eventsBehavior' => array(
				'class'=>'ext.proyecto.EventsBehavior',
			),
		);
	}
	/**
	 * Sobrescribe el metodo delete, para borrar el modelo usuficha.
	 */
	public function delete() {
		$this->usuficha->delete();
		if ($this->hasRelated('login') && is_object($this->login) ) $this->login->delete();
		$this->deleteCAR();
		return true;
	}
	/** 
	 * Marcar que es un superusuario
	 * @param boolean $opcion
	 */
	public function gestor($opcion=true) {
		if ($opcion) {
			$this->login->superuser = 1;
			$this->tipo = 'admin';
		} else {
			$this->login->superuser = 0;
			$this->tipo = 'usu';
		}
		$this->setModelHijos($this->login);
		return $this->save();
	}
	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'nombre' => 'Nombre',
			'fecha_creacion' => 'Fecha Creacion',
			'estado' => 'Estado',
			'id_especialidad' => 'Especialidad',
			'email' => 'Email',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @param string $inscribir es 'alumno' o 'profesor' o vacio. En ese caso no mostrará los inscritoa a un curso.
	 * @param integer $cu es el id_curso que se quiere inscribir.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search($inscribir = "",$cu=0)
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('nombre',$this->nombre,true);
		$lafecha = "";
		if (!empty($this->fecha_creacion)) {
			//res es 0 filtro 1 value
			$res = UtilGIeaem::separarFiltro($this->fecha_creacion);
			$lafecha = (isset($res[1]))?$res[0].UtilGIeaem::fechaForm2Mysql($res[1]):UtilGIeaem::fechaForm2Mysql($this->fecha_creacion);
		}
		$criteria->compare('date(fecha_creacion)',$lafecha);
		$criteria->compare('estado',$this->estado);
		$criteria->compare('tipo',$this->tipo);
		if ($this->id_especialidad == UtilGIeaem::INDEF) {
			$criteria->addCondition("id_especialidad is NULL");
		} else {
			$criteria->compare('id_especialidad',$this->id_especialidad);
		}
		$criteria->compare('email',$this->email,true);
		
		if ($inscribir == "profesor") {
			$fcc = new FCreateCommand();
			$prf = $fcc->config('profesor');
			$criteria->join="LEFT JOIN {$prf->tableAs()} ON (t.id = {$prf->col('id_usuario')} and {$prf->col('id_curso')} = $cu )";
			$criteria->addCondition("{$prf->col('id_usuario')} is NULL");
		} elseif ($inscribir == "alumno") {
			$fcc = new FCreateCommand();
			$alu = $fcc->config('alumno');
			$criteria->join="LEFT JOIN {$alu->tableAs()} ON (t.id = {$alu->col('id_usuario')} and {$alu->col('id_curso')} = $cu )";
			$criteria->addCondition("{$alu->col('id_usuario')} is NULL");
		}
		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
	/**
	 * Sobrescibe actulizando el id_usuario en el modelo UsuFicha
	 * @param UsuFicha $modelHijo
	 */
	protected function pasarDatosPadre($modelhijo) {
		$modelhijo->id_usuario = $this->id;	
	}
	/**
	 * Consultas directas a la base de datos sin pasar por el estudio de la estructura
	 */
	 public static function cmquery($tipo,$a1="",$a2="",$a3="") {
		switch ($tipo) {
			/* Ejemplo de uso
			case  'todos':
				return Yii::app()->db->createCommand('SELECT t.id,t.nombre FROM gi_usuario t')->queryAll();
				break;
			*/
			case  'profesores': //FIXME: FACER: Verison 1-0. A retirar
				return Yii::app()->db->createCommand('SELECT u.id,u.nombre FROM gi_usuario u LEFT JOIN gi_alumno a on a.id_usuario = u.id WHERE a.id is null')->queryAll();
				break;
			case 'alumnos': // FIXME: Facer: version 1-0. A retirar
				return Yii::app()->db->createCommand('SELECT u.id, u.nombre FROM gi_usuario u LEFT JOIN gi_alumno a ON a.id_usuario = u.id WHERE a.id IS NOT NULL AND a.id_curso ='.$a1);
				break;
			
			default:
				return array();
		}

	 }
	 public function porOmision() {
	 	$this->estado = 'activo';
	 }
	 /**
	 *
	 * Utíl para mostralo en un Select para escoger uno de los estados.
	 * @return array:string
	 */
	public static function getEstados($estado=NULL,$key=false) {
	 	static $estados = array(
	 			'activo'=>'Activo',
	 			'baja'=>'Baja'	
			 );
		if ($key) return array_keys($estados);
	 	return (isset($estado))? (isset($estados[$estado])?$estados[$estado]:false):$estados;
	}
	 /**
	 *
	 * Utíl para mostralo en un Select para escoger uno de los tipos.
	 * @return array:string
	 */
	public static function getTipos($tipo=NULL,$key=false) {
	 	static $tipos = array(
	 			'usu'=>'Usuario',
	 			'admin'=>'Gestor'	
			 );
		if ($key) return array_keys($tipos);
	 	return (isset($tipo))? (isset($tipos[$tipo])?$tipos[$tipo]:false):$tipos;
	}
	/**
	 * 
	 * En insert/update hay que recuperar los datos del hijo
	 * y estes pueden estar en modelHijos en relations o haya que crear una nueva.
	 *  
	 * @param string $relacion si se requieren el usuficha o el login.
	 * @return el objecto solicitado.
	 */
	public function getHijo($relacion) {
		$relaciones = $this->relations();
		$clase = $relaciones[$relacion][1];
		foreach ( (array) $this->modelHijos as $mh ) {
			if (get_class($mh) == $clase) return $mh;
		}
		if ($this->$relacion !== NULL) return $this->$relacion;
		else return new $clase();
	}
}