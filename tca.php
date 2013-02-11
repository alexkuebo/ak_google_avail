<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_akgoogleavail_calendar'] = array(
	'ctrl' => $TCA['tx_akgoogleavail_calendar']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'hidden,url'
	),
	'feInterface' => $TCA['tx_akgoogleavail_calendar']['feInterface'],
	'columns' => array(
		'hidden' => array(		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array(
				'type'    => 'check',
				'default' => '0'
			)
		),
		'url' => array(		
			'exclude' => 0,		
			'label' => 'LLL:EXT:ak_google_avail/locallang_db.xml:tx_akgoogleavail_calendar.url',		
			'config' => array(
				'type'     => 'input',
				'size'     => '15',
				'max'      => '255',
				'checkbox' => '',
				'eval'     => 'trim',
				'wizards'  => array(
					'_PADDING' => 2,
					'link'     => array(
						'type'         => 'popup',
						'title'        => 'Link',
						'icon'         => 'link_popup.gif',
						'script'       => 'browse_links.php?mode=wizard',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					)
				)
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'hidden;;1;;1-1-1, url')
	),
	'palettes' => array(
		'1' => array('showitem' => '')
	)
);
?>