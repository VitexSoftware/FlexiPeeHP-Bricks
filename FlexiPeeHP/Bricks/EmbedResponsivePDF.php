<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace FlexiPeeHP\Bricks;

/**
 * Description of EmbedResponsive
 *
 * @author vitex
 */
class EmbedResponsivePDF extends EmbedResponsive
{

    /**
     * Ebed Document's PDF to Page
     *
     * @param \FlexiPeeHP\FlexiBeeRO $source object with document
     * @param string                 $feeder script can send us the pdf
     */
    public function __construct($source, $feeder = 'getpdf.php')
    {
        $url = $feeder.'?evidence='.$source->getEvidence().'&id='.$source->getMyKey().'&embed=true';

        parent::__construct('<object data=\''.$url.'\' type=\'application/pdf\' width=\'100%\' height=\'100%\'></object>',
            ['class' => 'embed-responsive', 'style' => 'padding-bottom:150%']);
    }


}
