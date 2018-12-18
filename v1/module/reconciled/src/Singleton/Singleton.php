<?php
abstract class Singleton
{

    /**
     * This is an array of the different singletons instances, indexed
     * by class name.
     *
     * @var Singleton[]
     */
    private static $_instances = array();

    /**
     * Creates or returns the only instance of the a class.
     *
     * @return Singleton the only instance of the a class.
     */
    final public static function getInstance()
    {
        $calledClassName = get_called_class();

        if( !isset( self::$_instances[ $calledClassName ] ) )
        {
            self::$_instances[ $calledClassName ] = new $calledClassName();
        }

        return self::$_instances[ $calledClassName ];
    }

    /**
     * Create a clone of the instance.
     * Because this class is a singleton class, there can exists only one
     * instance of this class.
     * So this method will throw an exception if someone tries to call it.
     *
     * @throws BadMethodCallException An new BadMethodCallException instance
     * @return void
     */
    final public function __clone()
    {
        throw new BadMethodCallException(
            'This class is a singleton class, you are not allowed to clone it.' . "\n" .
            'Please call ' . get_class( $this ) . '::getInstance() to get a reference to ' .
            'the only instance of the ' . get_class( $this ) . ' class.'
        );
    }

    /**
     * This method is called when serialize() is called on a serialized representation
     * of an instance of this class.
     * Because this class is a singleton class, there can exists only one
     * instance of this class.
     * So this method will throw an exception if someone tries to call it.
     *
     * @throws BadMethodCallException An new BadMethodCallException instance
     * @return void
     */
    final public function __sleep()
    {
        throw new BadMethodCallException(
            'This class is a singleton class, you are not allowed to serialize ' .
            'it as this could create a new instance of it, when unserialized.' . "\n" .
            'Please call ' . get_class( $this ) . '::getInstance() to get a reference to ' .
            'the only instance of the ' . get_class( $this ) . ' class.'
        );
    }

    /**
     * This method is called when unserialize() is called on a serialized
     * representation of an instance of this class.
     * Because this class is a singleton class, there can exists only one
     * instance of this class.
     * So this method will throw an exception if someone tries to call it.
     *
     * @throws BadMethodCallException An new BadMethodCallException instance
     * @return void
     */
    final public function __wakeup()
    {
        throw new BadMethodCallException(
            'This class is a singleton class, you are not allowed to unserialize ' .
            'it as this could create a new instance of it.' . "\n" .
            'Please call ' . get_class( $this ) . '::getInstance() to get a reference to ' .
            'the only instance of the ' . get_class( $this ) . ' class.'
        );
    }

    /**
     * The constructor of this class cannot be used to instanciate a Singleton object.
     * This class is a singleton class, this means that there is only one instance
     * of the Singleton class and this instance is shared by every one who does a call
     * to Singleton::getInstance().
     *
     * @see getInstance to know how to create the Singleton instance or to get a reference to it.
     */
    final protected function __construct()
    {
        $this->_initializeObject();
    }

    /**
     * Initialize the object.
     * This method is called by the constructor and can be overrided in subclass
     * if needed.
     *
     * @return void
     */
    protected function _initializeObject()
    {

    }

}