<?php
class EyesRemoteWebElement extends RemoteWebElement {
    private $logger; //Logger
    private $eyesDriver; //EyesWebDriver
    private $webElement; //RemoteWebElement
    private $executeMethod; //Method

    const JS_GET_COMPUTED_STYLE_FORMATTED_STR =
        " return arguments;"
           /* "var elem = arguments[0];" .
            "var styleProp = '%s';" .
            "if (window.getComputedStyle) {" .
                "return window.getComputedStyle(arguments, null).getPropertyValue(styleProp);" .
            "} else if (elem.currentStyle) {" .
            "   return elem.currentStyle[styleProp];" .
            "} else {" .
                "return null;" .
            "}"*/;

    const JS_GET_SCROLL_LEFT =
            //"return arguments[0].scrollLeft;";
            "return window.pageXOffset;";

    const JS_GET_SCROLL_TOP =
            //"return arguments[0].scrollTop;";
            "return window.pageYOffset;";

    const JS_GET_SCROLL_WIDTH =
            "return arguments[0].scrollWidth;";

    const JS_GET_SCROLL_HEIGHT =
            "return arguments[0].scrollHeight;";

    const JS_SCROLL_TO_FORMATTED_STR =
            "window.scrollTo(%s, %s);";

    const JS_GET_OVERFLOW =
            "str = JSON.stringify(arguments); alert(str);";
            //"return arguments.innerHTML;";

    const JS_SET_OVERFLOW_FORMATTED_STR =
            "arguments.overflow = '%s'";

    public function __construct(Logger $logger, EyesWebDriver $eyesDriver,
                                RemoteWebElement $webElement) {
        //parent::__construct(); FIXME need to check

        ArgumentGuard::notNull($logger, "logger");
        ArgumentGuard::notNull($eyesDriver, "eyesDriver");
        ArgumentGuard::notNull($webElement, "webElement");

        $this->logger = $logger;
        $this->eyesDriver = $eyesDriver;
        $this->webElement = $webElement;

        try {
            // We can't call the execute method directly because it is
            // protected, and we must override this function since we don't
            // have the "parent" and "id" of the aggregated object.
            //FIXME need to check
            $this->executeMethod = new ReflectionMethod("RemoteExecuteMethod", "execute");
            //$executeMethod = RemoteWebElement.class.getDeclaredMethod("execute",
            //        String.class, Map.class);
            $this->executeMethod->setAccessible(true);
        } catch (NoSuchMethodException $e) {
            throw new EyesException("Failed to find 'execute' method!");
        }
    }

    public function getBounds() {
        $left = $this->webElement->getLocation()->getX();
        $top = $this->webElement->getLocation()->getY();
        $width = 0;
        $height = 0;

        try {
            $width = $this->webElement->getSize()->getWidth();
            $height = $this->webElement->getSize()->getHeight();
        } catch (Exception $ex) {
            // Not supported on all platforms.
        }

        if ($left < 0) {
            $width = Math::max(0, $width + $left);
            $left = 0;
        }

        if ($top < 0) {
            $height = Math::max(0, $height + $top);
            $top = 0;
        }

        return new Region($left, $top, $width, $height);
    }

    /**
     * Returns the computed value of the style property for the current
     * element.
     * @param propStyle The style property which value we would like to
     *                  extract.
     * @return The value of the style property of the element, or {@code null}.
     */
    public function getComputedStyle($propStyle) {
        $scriptToExec = sprintf
                (self::JS_GET_COMPUTED_STYLE_FORMATTED_STR, $propStyle);
        return $this->eyesDriver->getRemoteWebDriver()->executeScript($scriptToExec);
       // return $this->eyesDriver->getRemoteWebDriver()->execute($scriptToExec);
    }

    /**
     * @return The integer value of a computed style.
     */
    private function getComputedStyleInteger($propStyle) {
        return Math::round(Float::valueOf($this->getComputedStyle($propStyle)->trim()->
                replace("px", "")));
    }

    /**
     * @return The value of the scrollLeft property of the element.
     */
    public function getScrollLeft() {
        return $this->eyesDriver->executeScript(self::JS_GET_SCROLL_LEFT);
    }

    /**
     * @return The value of the scrollTop property of the element.
     */
    public function getScrollTop() {
        return $this->eyesDriver->executeScript(self::JS_GET_SCROLL_TOP);

    }

    /**
     * @return The value of the scrollWidth property of the element.
     */
    /*public function getScrollWidth() {
        return $this->eyesDriver->executeScript(self::JS_GET_SCROLL_WIDTH,
                $this)->toString();
    }*/

    /**
     * @return The value of the scrollHeight property of the element.
     */
    /*public function getScrollHeight() {
        return $this->eyesDriver->executeScript(self::JS_GET_SCROLL_HEIGHT,
                $this)->toString();
    }*/

    /**
     * @return The width of the left border.
     */
    public function getBorderLeftWidth() {
        //return $this->getComputedStyleInteger("border-left-width");
        return str_replace('px', '', $this->getCssValue("border-left-width")); //FIXME need to optimize
    }

    /**
     * @return The width of the right border.
     */
    public function getBorderRightWidth() {
        //return $this->getComputedStyleInteger("border-right-width");
        return str_replace('px', '', $this->getCssValue("border-right-width")); //FIXME need to optimize
    }

    /**
     * @return The width of the top border.
     */
    public function getBorderTopWidth() {
        //return $this->getComputedStyleInteger("border-top-width");
        return str_replace('px', '', $this->getCssValue("border-top-width")); //FIXME need to optimize
    }

    /**
     * @return The width of the bottom border.
     */
    public function getBorderBottomWidth() {
        //return $this->getComputedStyleInteger("border-bottom-width");
        return str_replace('px', '', $this->getCssValue("border-bottom-width")); //FIXME need to optimize
    }

    /**
     * Scrolls to the specified location inside the element.
     * @param location The location to scroll to.
     */
    public function scrollTo(Location $location) {
        $this->eyesDriver->executeScript(sprintf(self::JS_SCROLL_TO_FORMATTED_STR,
                $location->getX(), $location->getY()));
    }

    /**
     * @return The overflow of the element.
     */
    public function getOverflow() {
        return $this->getCssValue("overflow");
        //return $this->eyesDriver->getRemoteWebDriver()->executeScript(self::JS_GET_OVERFLOW, array(array(":id" => $this->getId())));

    }

    /**
     * Sets the overflow of the element.
     * @param overflow The overflow to set.
     */
    public function setOverflow($overflow) {
        $this->eyesDriver->executeScript(sprintf(self::JS_SET_OVERFLOW_FORMATTED_STR,
                $overflow));
    }

    public function click() {
        // Letting the driver know about the current action.
        $currentControl = $this->getBounds();
        $this->eyesDriver->getEyes()->addMouseTrigger(MouseAction::Click, $this);
        $this->logger->verbose(sprintf("click(%s)", $currentControl));

        $this->webElement->click();
    }

    public function getWrappedDriver() {
        return $this->eyesDriver;
    }

    public function getId() {
        return $this->webElement->getId();
    }

    public function setParent(RemoteWebDriver $parent) {
        $this->webElement->setParent($parent);
    }

    public function execute($command, /*Map<String, ?> */$parameters = array()) {
        try { //FIXME need to check
            return $this->eyesDriver->getRemoteWebDriver()->execute($command, $parameters);
        } catch (Exception $e) {
            throw new /*Eyes*/Exception("Failed to invoke 'execute' method!", $e);
        }

    }

    public function setId($id) {
        $this->webElement->setId($id);
    }

    public function setFileDetector(FileDetector $detector) {
        $this->webElement->setFileDetector($detector);
    }

    public function submit() {
        $this->webElement->submit();
    }

    public function sendKeys(/*CharSequence... */$keysToSend) {
        foreach ($keysToSend as $keys) {
            $this->eyesDriver->getEyes()->addTextTrigger($this, $keys);
        }

        $this->webElement->sendKeys($keysToSend);
    }

    public function clear() {
        $this->webElement->clear();
    }

    public function getTagName() {
        return $this->webElement->getTagName();
    }

    public function getAttribute($name) {
        return $this->webElement->getAttribute($name);
    }

    public function isSelected() {
        return $this->webElement->isSelected();
    }

    public function isEnabled() {
        return $this->webElement->isEnabled();
    }

    public function getText() {
        return $this->webElement->getText();
    }

    public function getCssValue($propertyName) {
        return $this->webElement->getCssValue($propertyName);
    }

    /**
     * For RemoteWebElement object, the function returns an
     * EyesRemoteWebElement object. For all other types of WebElement,
     * the function returns the original object.
     */
    private function wrapElement(WebElement $elementToWrap) {
        $resultElement = $elementToWrap; //FIXME clone?
        if ($elementToWrap instanceof RemoteWebElement) {
            $resultElement = new EyesRemoteWebElement($this->logger, $this->eyesDriver,
                    /*(RemoteWebElement) */$elementToWrap);
        }
        return $resultElement;
    }

    /**
     * For RemoteWebElement object, the function returns an
     * EyesRemoteWebElement object. For all other types of WebElement,
     * the function returns the original object.
     */
    private function wrapElements($elementsToWrap) {
        // This list will contain the found elements wrapped with our class.
        $wrappedElementsList = array();

        foreach ($elementsToWrap as $currentElement) {
            if ($currentElement instanceof RemoteWebElement) {
                $wrappedElementsList->add(new EyesRemoteWebElement($this->logger,
                        $this->eyesDriver, /*(RemoteWebElement) */$currentElement));
            } else {
                $wrappedElementsList->add($currentElement);
            }
        }

        return $wrappedElementsList;
    }

    public function findElements(WebDriverBy $by) {
        return $this->wrapElements($this->webElement->findElements($by));
    }

    public function findElement(WebDriverBy $by) {
        return $this->wrapElement($this->webElement->findElement($by));
    }

    public function findElementById($using) {
        return $this->wrapElement($this->webElement->findElementById($using));
    }

    public function findElementsById($using) {
        return $this->wrapElements($this->webElement.findElementsById($using));
    }

    public function findElementByLinkText($using) {
        return $this->wrapElement($this->webElement->findElementByLinkText($using));
    }

    public function findElementsByLinkText($using) {
        return $this->wrapElements($this->webElement->findElementsByLinkText($using));
    }

    public function findElementByName($using) {
        return $this->wrapElement($this->webElement->findElementByName($using));
    }

    public function findElementsByName($using) {
        return $this->wrapElements($this->webElement.findElementsByName($using));
    }

    public function findElementByClassName($using) {
        return $this->wrapElement($this->webElement->findElementByClassName($using));
    }

    public function findElementsByClassName($using) {
        return $this->wrapElements($this->webElement->findElementsByClassName($using));
    }

    public function findElementByCssSelector($using) {
        return $this->wrapElement($this->webElement->findElementByCssSelector($using));
    }

    public function findElementsByCssSelector($using) {
        return wrapElements($this->webElement.findElementsByCssSelector($using));
    }

    public function findElementByXPath($using) {
        return wrapElement($this->webElement.findElementByXPath($using));
    }

    public function findElementsByXPath($using) {
        return wrapElements($this->webElement->findElementsByXPath($using));
    }

    public function findElementByPartialLinkText($using) {
        return wrapElement($this->webElement->findElementByPartialLinkText($using));
    }

    public function findElementsByPartialLinkText($using) {
        return wrapElements($this->webElement->findElementsByPartialLinkText($using));
    }

    public function findElementByTagName($using) {
        return wrapElement($this->webElement->findElementByTagName($using));
    }

    public function findElementsByTagName($using) {
        return wrapElements($this->webElement->findElementsByTagName($using));
    }

    public function equals($obj) {
        return ($obj instanceof  RemoteWebElement) && ($this->webElement == $obj);
    }

    public function hashCode() {
        return $this->webElement->hashCode();
    }

    public function isDisplayed() {
        return $this->webElement->isDisplayed();
    }

    public function getLocation() {
        // This is workaround: Selenium currently just removes the value
        // after the decimal dot (instead of rounding up), which causes
        // incorrect locations to be returned when using ChromeDriver (with
        // FF it seems that the coordinates are already rounded up, so
        // there's no problem). So, we copied the code from the Selenium
        // client and instead of using "rawPoint.get(...).intValue()" we
        // return the double value, and use "ceil".
        $response = $this->execute(DriverCommand::GET_ELEMENT_LOCATION,
            array(":id"=>$this->getId()));//ImmutableMap::of("id", $elementId));
        //$rawPoint = $response->getValue();
        $x = ceil($response["x"]);
        $y = ceil($response["y"]);
        return new WebDriverPoint($x, $y);

        // TODO: Use the command delegation instead. (once the bug is fixed).
//        return webElement.getLocation();
    }

    public function getSize() {
        // This is workaround: Selenium currently just removes the value
        // after the decimal dot (instead of rounding up), which might cause
        // incorrect size to be returned . So, we copied the code from the
        // Selenium client and instead of using "rawPoint.get(...).intValue()"
        // we return the double value, and use "ceil".
        $elementId = $this->getId();
        $response = $this->execute(DriverCommand::GET_ELEMENT_SIZE,
            array(":id"=>$elementId));//ImmutableMap::of("id", elementId));
        //$rawSize = $response->getValue();
        $width = ceil($response["width"]);
        $height = ceil($response["height"]);
        return new WebDriverDimension($width, $height);

        // TODO: Use the command delegation instead. (once the bug is fixed).
//        return webElement.getSize();
    }

    public function getCoordinates() {
        return $this->webElement->getCoordinates();
    }

    public function toString() {
        return "EyesRemoteWebElement:" . $this->webElement->toString();
    }
}
