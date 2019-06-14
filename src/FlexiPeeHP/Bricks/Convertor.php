<?php
/**
 * FlexiPeeHP Bricks - Convertor Class
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2018 Vitex Software
 */

namespace FlexiPeeHP\Bricks;

/**
 * Description of Convertor
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Convertor extends \Ease\Sand
{
    /**
     * Source Object
     * @var \FlexiPeeHP\FlexiBeeRO 
     */
    private $input;

    /**
     * Destination Object
     * @var \FlexiPeeHP\FlexiBeeRO 
     */
    private $output;

    /**
     *
     * @var array 
     */
    private $rules = [];

    /**
     * Convertor 
     * 
     * @param \FlexiPeeHP\FlexiBeeRO $input
     * @param \FlexiPeeHP\FlexiBeeRW $output
     */
    public function __construct(\FlexiPeeHP\FlexiBeeRO $input = null,
                                \FlexiPeeHP\FlexiBeeRW $output = null)
    {
        parent::__construct();
        if (!empty($input)) {
            $this->setSource($input);
        }
        if (!empty($output)) {
            $this->setDestination($output);
        }
    }

    /**
     * Set Source Documnet
     * 
     * @param \FlexiPeeHP\FlexiBeeRO $source
     */
    public function setSource(\FlexiPeeHP\FlexiBeeRO $source)
    {
        $this->input = $source;
    }

    /**
     * Set Destination document
     * 
     * @param \FlexiPeeHP\FlexiBeeRO $destinantion
     */
    public function setDestination(\FlexiPeeHP\FlexiBeeRO $destination)
    {
        $this->output = $destination;
    }

    /**
     * Perform Conversion
     * 
     * @param boolean $keepId
     * @param boolean $addExtId
     * @param boolean $keepCode
     * @param boolean $handleAccounting set columns "ucetni" like target or ignore it
     * 
     * @return \FlexiPeeHP\FlexiBeeRW converted object ( unsaved )
     */
    public function conversion($keepId = false, $addExtId = false,
                               $keepCode = false, $handleAccounting = false)
    {
        $this->prepareRules($keepId, $addExtId, $keepCode, $handleAccounting);
        $this->convertDocument();
        return $this->output;
    }

    /**
     * Get Classname without namespace prefix
     * 
     * @param object $object
     * 
     * @return string
     */
    static public function baseClassName($object)
    {
        return basename(str_replace('\\', '/', get_class($object)));
    }

    /**
     * Prepare conversion rules
     * 
     * @throws \Ease\Exception
     */
    public function prepareRules($keepId, $addExtId, $keepCode,
                                 $handleAccounting)
    {
        $convertorClassname = $this->getConvertorClassName();
        $ruleClass          = '\\FlexiPeeHP\\Bricks\\ConvertRules\\'.$convertorClassname;
        if (class_exists($ruleClass, true)) {
            $this->rules = new $ruleClass($this, $keepId, $addExtId, $keepCode,
                $handleAccounting);
        } else {
            if ($this->debug) {
                ConvertorRule::convertorClassTemplateGenerator($this,
                    $convertorClassname);
            }
            throw new \Ease\Exception(sprintf(_('Cannot Load Class: %s'),
                    $ruleClass));
        }
    }

    public function getConvertorClassName()
    {
        return self::baseClassName($this->input).'_to_'.self::baseClassName($this->output);
    }

    /**
     * Convert FlexiBee document
     * 
     * @param boolean $keepId           keep item IDs
     * @param boolean $addExtId         add ext:originalEvidence:originalId 
     * @param boolean $keepCode         keep items code
     * @param boolean $handleAccounting set item's "ucetni" like target 
     */
    public function convertDocument($keepId = false, $addExtId = false,
                                    $keepCode = false, $handleAccountig = false)
    {
        $this->convertItems($keepId, $addExtId, $keepCode, $handleAccountig);
    }

    /**
     * Convert FlexiBee documnet's subitems
     * 
     * @param string  $columnToTake   usually "polozkyDokladu"
     * @param boolean $keepId         keep item IDs
     * @param boolean $keepCode       keep items code
     * @param boolean $keepAccounting set item's "ucetni" like target 
     */
    public function convertSubitems($columnToTake, $keepId = false,
                                    $keepCode = false, $keepAccountig = false)
    {
        $subitemRules = $this->rules[$columnToTake];
        if (self::isAssoc($this->input->data[$columnToTake])) {
            $sourceData = [$this->input->data[$columnToTake]];
        } else {
            $sourceData = $this->input->getDataValue($columnToTake);
        }

        $typUcOp = $this->input->getDataValue('typUcOp');

        foreach ($sourceData as $subItemData) {
            foreach (array_keys($subItemData) as $subitemColumn) {
                if (!array_key_exists($subitemColumn, $subitemRules)) {
                    unset($subItemData[$subitemColumn]);
                }
            }

            if ($keepAccountig && array_key_exists('ucetni', $subItemData) && array_key_exists('ucetni',
                    $this->output->getData())) {
                $subItemData['ucetni'] = $this->output->getDataValue('ucetni');
            } else {
                unset($subItemData['ucetni']);
            }

            if ($typUcOp) {
                $subItemData['typUcOp'] = $typUcOp;
            } else {
                unset($subItemData['typUcOp']);
            }

            if ($keepCode === false) {
                unset($subItemData['kod']);
            }
            if ($keepId === false) {
                unset($subItemData['id']);
                unset($subItemData['external-ids']);
            }
            $this->output->addArrayToBranch($subItemData);
        }
    }

    public function convertItems()
    {

        foreach ($this->rules->getRules() as $columnToTake => $subitemColumns) {
            if (is_array($subitemColumns)) {
                if (!empty($this->input->getSubItems())) {
                    $this->convertSubitems($columnToTake, $keepId, $keepCode,
                        $handleAccounting);
                }
            } else {
                if (strstr($subitemColumns, '()')) {
                      call_user_func(array( $this->rules , str_replace('()','',$subitemColumns )));
                } else {
                    $this->output->setDataValue($columnToTake,
                        $this->input->getDataValue($subitemColumns));
                }
            }
        }
    }

    /**
     * Return itemes that same on both sides
     * 
     * @return array
     */
    public function commonItems()
    {
        return array_intersect(array_keys($this->input->getColumnsInfo()),
            array_keys($this->output->getColumnsInfo()));
    }

    /**
     * Get input object here
     * 
     * @return \FlexiPeeHP\FlexiBeeRO
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Get output object here
     * 
     * @return \FlexiPeeHP\FlexiBeeRO
     */
    public function getOutput()
    {
        return $this->output;
    }
}
