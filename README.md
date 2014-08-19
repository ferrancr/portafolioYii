portafolio Yii
===
Portafolio con algunos ejemplos del código que se ha utilizado en el desarrollo de proyectos basados en 
Yii framework de PHP. Concretamente el código es extraído del desarrollo de una intranet para la 
administración de estudios de formación personalizados.

El proyecto se creo utilizando la  Versión 1.1.8 June 26, 2011 del framework de Yii

El Yii es un framework que:
Esta construido siguiendo el patrón del MVC Model, Vista y Controler

Separa en directorios distintos la estructura del framework  y la estructura del proyecto, guardados en
el directorio yii y en el directorio protected respectivamente. La organización de los directorios 
del proyecto es configurable, en este caso tenemos que se desarrolló siguiendo la estructura por defecto 
que sugiere Yii. 

En esté portafolio los directorios que están incluidos se utilizarón para los siguientes criterios :
* _components_ contiene componentes que expanden el Yii o clases de soporte
* _controllers_ contiene las clases que realizan las funciones de control
* _extensions_ contiene extensiones de terceros, en el portafolio está un ejemplo de extensión 
creada ex-proceso para el proyecto
* _models_  contiene los modelos del proyecto.
* _test_ contiene las pruebas unitarias del sistema

En el portafolio encontramos el código implicado en la definición y uso de "behaviors" para los modelos, 
el código responsable de la relacion entre modelos del tipo padre e hijo y un ejemplo de pruebas unitarias.
 
##Behaviors
Yii incorpora la infraestructura para poder definir atributos y métodos para un modelo mediante el añadido 
de comportamientos a dicho modelo

En este caso se creo un "comportamiento" que va a ser utilizado por todos aquellos modelos en los que 
se deseaba que quedase registro del nombre del usuario que creo el dato y en que fecha se realizó.

* En protected/extensions/proyecto/EventBehavior.php class _EventBehavior_ vemos la definición del "behavior", 
para actualizar y validar los atributos de usuario y fecha de creación y dos metodos extras _activo_ y _esCreador_
* En protected/models/Usuario.php funcion _behaviors_  se indica que al modelo de usuario 
se le añade dicho comportamiento.

Así que cuando se produzcan los eventos de guardar o validar de Usuario se ejecutara beforeValidate 
y/o el beforeSave, además el modelo _Usuario_ con el _EventBehavior_ incorpora dos métodos mas 
(_activo_ y _esCreador_) que puede llamar como si una función propia suya `$usuarioX->activo()`

## Definir relaciones entre modelos del tipo padre e hijo
El CArctiveRecord es la clase base para el tratamiento de los Modelos, entoces se ha creado un clase descendiente para
tratar los casos de relación entre modelos del tipo de padre e hijo, en donde el hijo solo existe si existe el padre.
* En protected/componets PHActiveRecord se han definido los métodos que sobrescriben el procedimiento normal
al momento de validar y guardar los datos para que  tengan en cuenta que no se pueda guardar un hijo 
sin el padre y tampoco que si hay errores en el hijo se actualice el padre. Además se definen un par de nuevos
eventos que puedes ser capturados por los modelos implicados llamados  _beforeActhijos_ y _afterActhijos_ 
respectivamente.
* En protected/models/Usuario.php está definido el modelo padre y protected/models/Usuficha.php está el hijo

## UnitTest
En protected/tests/unit/fechasTest.php se realizan 3 pruebas sobre el tratamiento de fechas.
* En la primera _testPrevio_ se utilizá para mostrar las características del metodo de Yii _CDateTimeParser::parse_
* En el segundo _testPaso1_ se prueba el funcionamiento de la función de ayuda de UtilGIeaem::desglosarPorPeriodos
que está en protected/components/UtilGIeaem.php
* En el tercer paso_ testValidator_ se prueba que el modelo _Usuficha_ valide correctamente el contenido del
campo de fecha de nacimiento.
 
