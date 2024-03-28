## Adding The Config Provider
* Enter config/config.php
* If there is no entry for the config provider below, add it:
  `\Dot\Log\ConfigProvider::class`
* Make sure it is added before with the Application-Specific components, eg.: \Frontend\App\ConfigProvider.php, `\Admin\App\ConfigProvider::class`, `MyProject\ConfigProvider::class` , etc.
* Open the `Dot\Log\ConfigProvider`
* In the dependencies section you will see an absctract factory (LoggerAbstractServiceFactory::class)
* This class responds to "selectors" instead of class names
    - Instead of requesting the `Laminas\Log\Logger::class`from the container, dot-log.my_logger should be requested (or just `my_logger` if using laminas-log)