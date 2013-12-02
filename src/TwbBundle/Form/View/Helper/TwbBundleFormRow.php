<?php
namespace TwbBundle\Form\View\Helper;

use Zend\Form\View\Helper\FormRow;

class TwbBundleFormRow extends FormRow
{
	/**
	 * @var string
	 */
	private static $formGroupFormat = '<div class="form-group %s">%s</div>';

	/**
	 * @var string
	 */
	private static $horizontalLayoutFormat = '%s<div class="%s">%s%s%s</div>';

	/**
	 * @var string
	 */
	private static $helpBlockFormat = '<p class="help-block">%s</p>';
	
	/**
	 * @var stirng
	 */
	protected $layout = TwbBundleForm::LAYOUT_HORIZONTAL;

	/**
	 * @see \Zend\Form\View\Helper\FormRow::render()
	 * @param \Zend\Form\ElementInterface $oElement
	 * @return string
	 */
	public function render(\Zend\Form\ElementInterface $element)
	{
	    $options = $element->getOptions();
	    
	    //Retrieve expected layout
	    if (isset($options['layout'])) {
	        if ($options['layout'] == 'vertical') $this->layout = null;
	        else $this->layout = $options['layout'];
	    }
		
		//Partial rendering
		if ($this->partial) {
		    return $this->view->render($this->partial, array(
		        'element' => $element,
		        'label'   => $this->renderLabel($element),
		        'labelAttributes' => $this->labelAttributes,
		        'labelPosition' => $this->labelPosition,
		        'renderErrors' => $this->renderErrors,
		    ));
		}

		$rowClass = '';
		//Validation state
		if (isset($options['validation-state']))
		    $rowClass .= ' has-' . $options['validation-state'];
		
		//"has-error" validation state case
		if (count($element->getMessages()))
		    $rowClass .= ' has-error';
		
		//Column size
        if (isset($options['column-size']))
            $rowClass .= 'col-lg-' . $options['column-size'];
        
        //Render element
        $elementContent = $this->renderElement($element);
        
        //Render form row
        $elementType = $element->getAttribute('type');
        if (in_array($elementType, array('checkbox')) && $this->layout !== TwbBundleForm::LAYOUT_HORIZONTAL)
            return $elementContent . PHP_EOL;
        if($elementType === 'submit' && $this->layout === TwbBundleForm::LAYOUT_INLINE)
            return $elementContent . PHP_EOL;
        
        return sprintf(
                self::$formGroupFormat,
                $rowClass,
                $elementContent
        ) . PHP_EOL;
	}

    /**
     * Render element's label
     * @param \Zend\Form\ElementInterface $element
     * @return string
     */
    protected function renderLabel(\Zend\Form\ElementInterface $element)
    {
        $label      = $element->getLabel();
        $translator = $this->getTranslator();
        if($label && $translator)
            $label = $translator->translate($label, $this->getTranslatorTextDomain());
        
        return $label;
    }
    
    /**
     * Render element
     * @param \Zend\Form\ElementInterface $element
     * @return string
     */
    protected function renderElement(\Zend\Form\ElementInterface $element)
    {
        //Render label
        $labelContent = $this->renderLabel($element);
        if ($labelContent) {
            //Multicheckbox elements have to be handled differently as the HTML standard does not allow nested labels. The semantic way is to group them inside a fieldset
            $elementType = $element->getAttribute('type');
            
            //Checkbox & radio elements are a special case, because label is rendered by their own helper
            if (in_array($elementType, array('multi_checkbox','checkbox','radio'))) {
                if(!$element->getLabelAttributes() && $this->labelAttributes)
                    $element->setLabelAttributes($this->labelAttributes);
                
                //Render element input
                if($this->layout !== TwbBundleForm::LAYOUT_HORIZONTAL)
                    return $this->getElementHelper()->render($element);
                
                $labelOpen = $labelClose = $labelContent = '';
            }
            
            //Button element is a special case, because label is always rendered inside it
            elseif ($element instanceof \Zend\Form\Element\Button)
                $labelOpen = $labelClose = $labelContent = '';
            else {
                $labelAttributes = $element->getLabelAttributes() ?: $this->labelAttributes;
                
                //Validation state
                if ($element->getOption('validation-state') || count($element->getMessages())) {
                    if (empty($labelAttributes['class']))
                        $labelAttributes['class'] = 'control-label';
                    elseif (!preg_match('/(\s|^)control-label(\s|$)/', $labelAttributes['class']))
                        $labelAttributes['class'] = trim($labelAttributes['class'] . ' control-label');
                }
                
                $labelHelper = $this->getLabelHelper();
                switch ($this->layout) {
                    //Hide label for "inline" layout
                    case TwbBundleForm::LAYOUT_INLINE :
                        if (empty($labelAttributes['class']))
                            $labelAttributes['class'] = 'sr-only';
                        elseif (!preg_match('/(\s|^)sr-only(\s|$)/', $labelAttributes['class']))
                            $labelAttributes['class'] = trim($labelAttributes['class'] . ' sr-only');
                        break;
                        
                    case TwbBundleForm::LAYOUT_HORIZONTAL :
                        if (empty($labelAttributes['class']))
                            $labelAttributes['class'] = 'col-lg-2 control-label';
                        else {
                            if (!preg_match('/(\s|^)col-lg-2(\s|$)/', $labelAttributes['class']))
                                $labelAttributes['class'] = trim($labelAttributes['class'] . ' col-lg-2');
                            if (!preg_match('/(\s|^)control-label(\s|$)/', $labelAttributes['class']))
                                $labelAttributes['class'] = trim($labelAttributes['class'] . ' control-label');
                        }
                        break;
                }
                
                if($labelAttributes) $element->setLabelAttributes($labelAttributes);
                
                $labelOpen  = $labelHelper->openTag($element);
                $labelClose = $labelHelper->closeTag();
                $labelContent = $this->getEscapeHtmlHelper()->__invoke($labelContent);
            }
            
            
            
            switch ($this->layout) {
                case null :
                case TwbBundleForm::LAYOUT_INLINE :
                    return $labelOpen . $labelContent . $labelClose . $this->getElementHelper()->render($element);
                    
                default :
                case TwbBundleForm::LAYOUT_HORIZONTAL :
                    $class = 'col-lg-10';
                    
                    //Element without labels
                    if (!$labelContent) $class .= ' col-lg-offset-2';
                    
                    //Render help block
                    $helpBlock = $this->renderHelpBlock($element);
                    //Render errors
                    $errors    = $this->renderErrors($element);
                    
                    return sprintf(
                            self::$horizontalLayoutFormat,
                            $labelOpen . $labelContent . $labelClose,
                            $class,
                            $this->getElementHelper()->render($element),
                            $helpBlock,
                            $errors
                    );
            }
        }
        
        //Render element input
        return $this->getElementHelper()->render($element);
    }
    
    /**
     * Render errors
     * @param \Zend\Form\ElementInterface $element
     * @return string
     */
    protected function renderErrors(\Zend\Form\ElementInterface $element)
    {
        //Element have errors
        $inputErrorClass = $this->getInputErrorClass();
        if (count($element->getMessages()) && $inputErrorClass) {
            $elementClass = $element->getAttribute('class');
            if ($elementClass) {
                if (!preg_match('/(\s|^)' . preg_quote($inputErrorClass, '/') . '(\s|$)/', $elementClass))
                    $element->setAttribute('class', trim($elementClass . ' ' . $inputErrorClass));
            }
            else $element->setAttribute('class', $inputErrorClass);
        }
        
        return $this->renderErrors ? $this->getElementErrorsHelper()->render($element) : '';
    }
    
    /**
     * Render element's help block
     * @param \Zend\Form\ElementInterface $element
     * @return string
     */
    protected function renderHelpBlock(\Zend\Form\ElementInterface $element)
    {
        $helpBlock = $element->getOption('help-block');
        $helpBlock = !empty($helpBlock) ? $helpBlock : $element->getOption('description');
        
        $translator = $this->getTranslator();
        return ($helpBlock) ? sprintf(
                self::$helpBlockFormat,
                $this->getEscapeHtmlHelper()->__invoke($translator ? $translator->translate($helpBlock, $this->getTranslatorTextDomain()) : $helpBlock)
        ) : '';
    }
}
