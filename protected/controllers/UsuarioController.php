<?php

class UsuarioController extends Controller
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column2';
	/**
	 * @var string Nombre de la página de wiki;
	 */
	public $wiki='Usuario';
	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
		array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('index','view'),
				'users'=>array('@'),
		),
		array('allow', // allow admin user to perform 'admin' and 'delete' actions
				'actions'=>array('create','update','admin','delete','inscurso'),
				'expression' => "ACeaem::checkAccess('admin')",
		),
		array('allow', 
				'actions'=>array('admins','admret'),
				'expression' => "ACeaem::esSuper()",
		),
		
		array('deny',  // deny all users
				'users'=>array('*'),
		),
		);
	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModelCompleto($id),
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate($cu=0,$pre="")
	{
		if ($cu != 0) {
			$id_curso = intval($cu);
			if ($pre == "alumno") $pre = 'alumno';
			else $pre = 'profesor';
			$curso = Curso::model()->findByPk($id_curso);
			if ($curso == NULL) {
				throw CDbException('400','Peticion incorrecta. Curso inexistente.');
			}
		}
		$model=new Usuario;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);
		$this->performAjaxDatoMaestro($model);
		if(isset($_POST['Usuario']))
		{
			$this->formToModel($model,'Usuario','Login','Usuficha',$_POST);
			$transaction = $model->DbConnection->beginTransaction();
			$sw = false;
			try {
				$sw = $model->save();
				if ($sw) $transaction->commit();
				else $transaction->rollBack();
			} catch (Exception $e) {
				$transaction->rollBack();
				//TODO: FACER: Reconvertir el menssage.
				$model->addError('id',$e->getMessage());
			}
			if($sw) {
				if ($cu == 0 )
					$this->redirect(array('view','id'=>$model->id));
				else 
					$this->redirect(array("$pre/create","usu"=>$model->id,"cu"=>$curso->id));
			}
		} else {
			$model->porOmision();
		}
		$this->render('create',array(
			'model'=>$model,
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$model=$this->loadModelCompleto($id);
		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);
		$this->performAjaxDatoMaestro($model);
		if(isset($_POST['Usuario']))
		{
			$this->formToModel($model,'Usuario','Login','Usuficha',$_POST);
			$transaction = $model->DbConnection->beginTransaction();
			
			$sw = false;
			try {
				$sw = $model->save();
				if ($sw) $transaction->commit();
				else $transaction->rollBack();
			} catch (Exception $e) {
				//TODO: FACER: Reconvertir el menssage.
				$transaction->rollBack();
				$model->addError('id',$e->getMessage());
			}
			if($sw)
				$this->redirect(array('view','id'=>$model->id));
		}

		$this->render('update',array(
			'model'=>$model,
		));
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		if(Yii::app()->request->isPostRequest)
		{
			// we only allow deletion via POST request
			//$this->loadModel($id)->delete();
			$model = $this->loadModelCompleto($id);
			$model->baja = true; // Inecesario desde que delete ya lo hace.
			// $this->formToModel($model,'Usuario','Login','Usuficha',$_POST);
			$transaction = $model->DbConnection->beginTransaction();
			$sw = false;
			try {
				$sw = $model->delete();
				if ($sw) $transaction->commit();
				else $transaction->rollBack();
			} catch (Exception $e) {
				$aviso = UtilGIeaem::filtrarErroresDB($e,array('1451'=>'No se puede borrar por que ya existe un profesor o un alumno con este usuario') );
				$model->addError('id',$aviso);
				$transaction->rollBack();
			}
			if(!$sw) {
				UtilGIeaem::flashErrorModel($model);
				$this->redirect(array('view','id'=>$model->id));
			}
			
			// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
			if(!isset($_GET['ajax'])) {
				Yii::app()->user->setFlash('success',"Borrado el usuario ".$model->nombre);
				$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
			}
		}
		else
		throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex()
	{
		$model=new Usuario('search');
		$model->unsetAttributes();  // clear any default values
		
		if(isset($_GET['Usuario']))
		$model->attributes=$_GET['Usuario'];
		$this->render('index',array(
			'model'=>$model,
		));
		
	}
	/**
	 * 
	 * Lista todos los que pueden inscribirse a un curso, ya sea como profesor
	 * o como alumno.
	 * @param integer $cu el id_curso;
	 * @param string $pre es 'alumno' o 'profesor'
	 */
	public function actionInscurso($cu,$pre)
	{
		$id_curso = intval($cu);
		if ($pre == "alumno") $pre = 'alumno';
		else $pre = 'profesor';
		$curso = Curso::model()->findByPk($id_curso);
		if ($curso == NULL) {
			throw CDbException('400','Peticion incorrecta. Curso inexistente.');
		}
		$model=new Usuario('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['Usuario']))
		$model->attributes=$_GET['Usuario'];
		$this->render('inscurso',array(
			'model'=>$model,
			'curso'=>$curso,
			'pre'=>$pre,
		));
	}
	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model=new Usuario('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['Usuario']))
		$model->attributes=$_GET['Usuario'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}
	public function actionAdmins($id) {
		$this->gestor(true,$id);
	}
	public function actionAdmret($id) {
		$this->gestor(false,$id);
	}
	public function gestor($opcion,$id) {
		if(Yii::app()->request->isPostRequest)
		{
			$model = $this->loadModelCompleto($id);
			$model->scenario='search'; //Por lo de safe. 
			$transaction = $model->DbConnection->beginTransaction();
			$sw = false;
			try {
				$sw = $model->gestor($opcion);
				if ($sw) $transaction->commit();
				else $transaction->rollBack();
			} catch (Exception $e) {
				$aviso = UtilGIeaem::filtrarErroresDB($e,array() );
				$model->addError('id',$aviso);
				$transaction->rollBack();
			}
			if(!$sw) {
				UtilGIeaem::flashErrorModel($model);
				$this->redirect(array('view','id'=>$model->id));
			}
			
			// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
			if(!isset($_GET['ajax'])) {
				Yii::app()->user->setFlash('success',"Solicitud ejecutada correctamente ".$model->nombre);
				$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('view','id'=>$model->id));
			}
		}
		else
		throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
	}
	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer the ID of the model to be loaded
	 */
	public function loadModel($id)
	{
		$model=Usuario::model()->findByPk($id);
		if($model===null)
		throw new CHttpException(404,'La página solicitada no existe');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='usuario-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
	protected function performAjaxDatoMaestro($model) {
		if(isset($_POST['AJAXDM']) && $_POST['pais_residencia'] ) {
			echo UtilGIeaem::AjaxSelectOptions(DatosMaestros::paramQueryRef('provincia',$_POST['pais_residencia']),array('indefinido'=>true));
			exit();
		}
	}
	
	public function loadModelCompleto($id) {
		$model=Usuario::model()->with(
			'login',
			'usuficha')->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'La página solicitada no existe');
		return $model;
	}
	public function formToModel($model,$usuario,$login,$usuficha,$post) {
		$lgModel = false;
		$fichModel = false;
		
		if (! isset($post[$usuario]) && $model->baja === false ) 
			return;
		if ($model->baja === false ) {
			$model->attributes=$post[$usuario];
		}
		if (isset($post[$usuficha]) ) {
			if ($model->isNewRecord || $model->usuficha === NULL ) {
				$fichModel = new Usuficha('porlotes'); // 'porlotes'
			} else {
				$fichModel = $model->usuficha;
			}
			if ($model->baja === false ) {
				 $fichModel->attributes = $post[$usuficha];
			} else {
				$fichModel->baja = true;
			}
			$fichModel->setScenario('porlotes');
			$model->setModelHijos($fichModel);
		}
		// Es una opción que tenga o no tenga login.
		$chklogin = isset($post['chklogin']);

		if ( $model->login === Null && ! $chklogin ) // No hay que hacer nada. 
			return;
			
		// Si no esta en la base de datos pero por formulario se marca que esté.
		if ( $model->login === Null ) {
			$lgModel = new Login(); // 
		} else  { // la opcion que queda es que esta e independiente
			$lgModel = $model->login;
		}
		if ( $model->baja == true  ) $lgModel->baja = true; // Hay que borrarlo.
		else $lgModel->initPost($post[$login],($chklogin && $model->estado == 'activo' && ! $model->baja )); 	
		$lgModel->setScenario('porlotes');
		$model->setModelHijos($lgModel);
	}
}
