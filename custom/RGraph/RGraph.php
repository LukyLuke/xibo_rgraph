<?php
/*
 * RGraph Xibo Module
 * Copyright (C) 2017 Lukas Zurschmiede
 *
 * This file is not part of Xibo, it's a custom module for it.
 *
 * This Xibo-Module is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version. 
 *
 * This Xibo-Module is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with This Xibo-Module.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Xibo\Custom\RGraph;

use InvalidArgumentException;
use Respect\Validation\Validator as v;

class RGraph extends \Xibo\Widget\ModuleWidget {
	const DEFAULT_COLORS = '#00f, #0f0, #f00, #0ff, #ff0, #f0f';
	
	public $codeSchemaVersion = 1;
	private $resourceFolder;

	/**
	 * RGraph constructor.
	 * @Override
	 */
	public function init() {
		$this->resourceFolder = PROJECT_ROOT . '/web/modules/RGraph';

		// Initialise extra validation rules
		v::with('Xibo\\Validation\\Rules\\');
	}
	
	/**
	 * Install or Update this module
	 * @param ModuleFactory $moduleFactory
	 * @Override
	 */
	public function installOrUpdate($moduleFactory) {
		// Install
		if ($this->module == null) {
			$module = $moduleFactory->createEmpty();
			$module->name = 'RGraph';
			$module->type = 'rgraph';
			$module->viewPath = '../custom/RGraph';
			$module->class = 'Xibo\Custom\RGraph\RGraph';
			$module->description = 'Graphical data visualization';
			$module->imageUri = 'forms/library.gif';
			$module->enabled = 1;
			$module->previewEnabled = 1;
			$module->assignable = 1;
			$module->regionSpecific = 1;
			$module->renderAs = 'html';
			$module->schemaVersion = $this->codeSchemaVersion;
			$module->defaultDuration = 240;
			$module->settings = [];

			$this->setModule($module);
			$this->installModule();
		}

		// Check we are all installed
		$this->installFiles();
	}

	/**
	 * Install all Javascript-Files provided by RGraph
	 */
	public function installFiles() {
		$sourcePath = PROJECT_ROOT . '/web/modules/vendor/rgraph/';
		$dir = opendir($sourcePath);
		while ($dir && ($file = readdir($dir)) !== false) {
			if (substr($file, -3) == '.js') {
				$this->mediaFactory->createModuleSystemFile($sourcePath . $file)->save();
			}
		}
	}

	/**
	 * Form for updating the module settings
	 * @return Name of the Settings-Form
	 * @Override
	 */
	public function settingsForm() {
		return 'rgraph-form-settings';
	}

	/**
	 * Process any module settings
	 * @return An array of the processed settings.
	 * @Override
	 */
	public function settings() {
		$this->module->settings['defaultColors'] = $this->getSanitizer()->getString('defaultColors', self::DEFAULT_COLORS);
		return $this->module->settings;
	}

	/**
	 * DataSets
	 * @return array[DataSet]
	 */
	public function dataSets() {
		return $this->dataSetFactory->query();
	}

	/**
	 * Validates the settings
	 * @Override
	 */
	public function validate() {
		if ($this->getUseDuration() == 1 && $this->getDuration() == 0)
			throw new InvalidArgumentException(__('You must enter a duration.'));
	}

	/**
	 * Adds a Widget
	 * @Override
	 */
	public function add() {
		$this->setCommonOptions();
		$this->validate();
		$this->saveWidget();
	}

	/**
	 * Edit the Widget
	 * @Override
	 */
	public function edit() {
		$this->setCommonOptions();
		$this->validate();
		$this->saveWidget();
	}

	/**
	 * Set common options from Request Params
	 */
	private function setCommonOptions() {
		$this->setOption('name', $this->getSanitizer()->getString('name'));
		$this->setUseDuration($this->getSanitizer()->getCheckbox('useDuration'));
		$this->setDuration($this->getSanitizer()->getInt('duration', $this->getDuration()));
		$this->setOption('rendering', $this->getSanitizer()->getString('rendering'));
		$this->setOption('graphType', $this->getSanitizer()->getString('graphType'));
		$this->setOption('backgroundColor', $this->getSanitizer()->getString('backgroundColor'));

		$this->setOption('dataSource', $this->getSanitizer()->getString('dataSource'));
		$this->setOption('dataset', $this->getSanitizer()->getString('dataset'));
		$this->setOption('labelColumn', $this->getSanitizer()->getString('labelColumn'));
                        
		$this->setOption('uri', urlencode($this->getSanitizer()->getString('uri')));
		$this->setOption('method', $this->getSanitizer()->getString('method'));
		$this->setRawNode('postData', $this->getSanitizer()->getParam('postData', null));

		$this->setOption('uri2', urlencode($this->getSanitizer()->getString('uri2')));
		$this->setOption('method2', $this->getSanitizer()->getString('method2'));
		$this->setRawNode('postData2', $this->getSanitizer()->getParam('postData2', null));

		$this->setOption('showLegend', $this->getSanitizer()->getCheckbox('showLegend', 0));
		$this->setOption('legendCenter', $this->getSanitizer()->getCheckbox('legendCenter', 0));
		$this->setOption('legendX', $this->getSanitizer()->getInt('legendX', 0));
		$this->setOption('legendY', $this->getSanitizer()->getInt('legendY', 0));
		$this->setOption('legendRight', $this->getSanitizer()->getCheckbox('legendRight', 0));
		$this->setOption('legendBottom', $this->getSanitizer()->getCheckbox('legendBottom', 0));

		$this->setRawNode('legendJavaScript', $this->getSanitizer()->getParam('legendJavaScript', null));
		$this->setRawNode('dataJavaScript', $this->getSanitizer()->getParam('dataJavaScript', null));
	}

	/**
	 * Preview code for a module
	 * 
	 * @param int $width
	 * @param int $height
	 * @param int $scaleOverride The Scale Override
	 * @return string The Rendered Content
	 * @Override
	 */
	public function preview($width, $height, $scaleOverride = 0) {
		return $this->previewAsClient($width, $height, $scaleOverride);
	}

	/**
	 * GetResource for the Graph page
	 * 
	 * @param int $displayId
	 * @return mixed|string
	 * @Override
	 */
	public function getResource($displayId = 0) {
		// Load in the template
		$data = [];

		// Replace the View Port Width?
		$isPreview = ($this->getSanitizer()->getCheckbox('preview') == 1);

		// Replace the View Port Width?
		$data['viewPortWidth'] = ($isPreview) ? $this->region->width : '[[ViewPortWidth]]';

		// Options XIBO needs for rendering - see JavaScript at the end of this function
		$options = array(
			'type' => $this->getModuleType(),
			'fx' => $this->getOption('effect', 'noAnim'),
			'speed' => $this->getOption('speed', 500),
			'useDuration' => $this->getUseDuration(),
			'duration' => $this->getDuration(),
			'originalWidth' => $this->region->width,
			'originalHeight' => $this->region->height,
			'previewWidth' => $this->getSanitizer()->getDouble('width', 0),
			'previewHeight' => $this->getSanitizer()->getDouble('height', 0),
			'scaleOverride' => $this->getSanitizer()->getDouble('scale_override', 0),
		);
		
		// Use SVG or Canvas?
		$svg = $this->getOption('rendering') == 'render_with_svg' ? '.SVG' : '';

		// Head Content contains all needed scrips from RGraph
		$jsObject = '';
		if ($this->getOption('rendering') == 'render_with_svg') {
			$headContent  = '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/rgraph/RGraph.SVG.common.core.js') . '"></script>'."\n";
			$headContent .= '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/rgraph/RGraph.SVG.common.ajax.js') . '"></script>'."\n";
			$headContent .= '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/rgraph/RGraph.SVG.common.fx.js') . '"></script>'."\n";
		} else {
			$headContent  = '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/rgraph/RGraph.common.core.js') . '"></script>'."\n";
			$headContent .= '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/rgraph/RGraph.common.dynamic.js') . '"></script>'."\n";
			$headContent .= '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/rgraph/RGraph.common.effects.js') . '"></script>'."\n";
		}
		
		switch ($this->getOption('graphType')) {
			case 'pie_chart':
				$jsObject = 'RGraph.Pie';
				$headContent .= '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/rgraph/RGraph' . $svg . '.pie.js') . '"></script>';
				break;
			case 'bar_chart':
				$jsObject = 'RGraph.Bar';
				$headContent .= '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/rgraph/RGraph' . $svg . '.bar.js') . '"></script>';
				break;
			case 'horizontal_bar_chart':
				$jsObject = 'RGraph.HBar';
				$headContent .= '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/rgraph/RGraph' . $svg . '.hbar.js') . '"></script>';
				break;
			case 'waterfall_chart':
				$jsObject = 'RGraph.Waterfall';
				$headContent .= '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/rgraph/RGraph' . $svg . '.waterfall.js') . '"></script>';
				break;
			case 'circular_progress':
				$jsObject = 'RGraph.SemiCircularProgress';
				$headContent .= '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/rgraph/RGraph' . $svg . '.semicircularprogress.js') . '"></script>';
				break;
			case 'vertical_progress':
				$jsObject = 'RGraph.Bar';
				$headContent .= '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/rgraph/RGraph' . $svg . '.bar.js') . '"></script>';
				break;
			case 'horizontal_progress':
				$jsObject = 'RGraph.HBar';
				$headContent .= '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/rgraph/RGraph' . $svg . '.hbar.js') . '"></script>';
				break;
			case 'radar_chart':
				$jsObject = 'RGraph.Radar';
				$headContent .= '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/rgraph/RGraph' . $svg . '.radar.js') . '"></script>';
				break;
			case 'scatter_chart':
				$jsObject = 'RGraph.Scatter';
				$headContent .= '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/rgraph/RGraph' . $svg . '.scatter.js') . '"></script>';
				break;
			case 'line_chart':
			default:
				$jsObject = 'RGraph.Line';
				$headContent .= '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/rgraph/RGraph' . $svg . '.line.js') . '"></script>';
				break;
		}
		$headContent .= '<style type="text/css">.graphLegend { position:absolute;display:inline-block;z-index:9999;text-align:left;border: 1px solid #ddd; box-shadow: 1px 1px 2px #ccc;padding:0.5em 0.8em;line-height:1.8em; } .graphLegend div { font-weight:bold; } .legendWrapper { width:100%;top:0;left:0;text-align:center; }</style>';
		$data['head'] = $headContent;

		// Body content
		$containerId = 'rgraph-' . $displayId;
		$legend = '';
		if ($this->getOption('showLegend') == 1) {
			$legendStyle = '';
			
			// Horizontal alignment
			if ($this->getOption('legendRight') == 1) {
				$legendStyle .= 'right:' . $this->getOption('legendX') . 'px;';
			} else if ($this->getOption('legendCenter') == 0) {
				$legendStyle .= 'left:' . $this->getOption('legendX') . 'px;';
			}
			
			// Vertical alignment
			if ($this->getOption('legendBottom') == 1) {
				$legendStyle .= 'bottom:' . $this->getOption('legendY') . 'px;';
			} else {
				$legendStyle .= 'top:' . $this->getOption('legendY') . 'px;';
			}
			$legend = '<div id="' . $containerId . '_legend" class="graphLegend" style="' . $legendStyle . '"></div>';
			
			if ($this->getOption('legendCenter') == 1) {
				$legend = '<div class="legendWrapper">' . $legend . '</div>';
			}
		}

		$data['body'] = '<div id="' . $containerId . '">
			<canvas id="' . $containerId . '_graph" width="' . $this->region->width . '" height="' . $this->region->height . '" style="border: 1px solid #ddd; box-shadow: 1px 1px 2px #ccc">[No Canvas support :o(]</canvas>
			' . $legend . '
		</div>';

		// Java-Script content to fetch the data
		$closeAdditional = false;
		$jsDataFetch = '';

		// Different functions for internal Datasets and JSON-RPC
		if ($this->getOption('dataSource') == 'dataset') {
			$jsDataFetch .= '(function(json, json2) {';
			
		} else {
			// Clean the URI for JSON-Requests
			$url_first = urldecode($this->getOption('uri'));
			$url_first = (preg_match('/^' . preg_quote('http') . "/", $url_first)) ? $url_first : 'http://' . $url_first;
			$post_first = urlencode($this->getRawNode('postData'));

			$url_second = urldecode($this->getOption('uri2'));
			$url_second = (preg_match('/^' . preg_quote('http') . "/", $url_second)) ? $url_second : 'http://' . $url_second;
			$post_second = urlencode($this->getRawNode('postData2'));

			// Prepare Javascript for using GET/POST and JSON Requests
			if ($this->getOption('method') == 'methodPost') {
				$jsDataFetch .= 'RGraph.AJAX.POST("' . $url_first . '", "' . $post_first . '", function(json) {';
			} else if ($this->getOption('method') == 'methodGet') {
				$jsDataFetch .= 'RGraph.AJAX.getJSON("' . $url_first . '", function(json) {';
			}

			// Check for a second RPC
			if ($this->getOption('method2') != 'methodSelect') {
				$closeAdditional = true;
				$jsDataFetch .= '
					var uri = template_replace("' . $url_second . '");
					var data = template_replace("' . $post_second . '");
					function template_replace(str) {
						var match = str.match(/\$\{[a-z0-9\[\]_.-]+\}/gi);
						if (match) {
							match.forEach(function(v) {
								eval("var x = json" + v.substr(2, v.length - 3));
								str = str.replace(v, x);
							});
						}
						return str;
					}
				';
			} else {
				$jsDataFetch .= 'var json2 = {};';
			}
			if ($this->getOption('method2') == 'methodPost') {
				$jsDataFetch .= 'RGraph.AJAX.POST(uri, data, function(json2) {';
			} else if ($this->getOption('method2') == 'methodGet') {
				$jsDataFetch .= 'RGraph.AJAX.getJSON(uri, function(json2) {';
			}
		}

		// After body content - mostly XIBO-Stuff for scaling and so on
		$javaScriptContent  = '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/jquery-1.11.1.min.js') . '"></script>';
		$javaScriptContent .= '<script type="text/javascript" src="' . $this->getResourceUrl('xibo-layout-scaler.js') . '"></script>';

		$javaScriptContent .= '<script>
		' . $this->getRawNode('legendJavaScript') . '
		' . $this->getRawNode('dataJavaScript') . '
		</script>'.chr(10);

		$javaScriptContent .= '<script>
			$(document).ready(function() {
				var options = ' . json_encode($options) . '
				$("#' . $containerId . '").xiboLayoutScaler(options);
				
				var graphOptions = ' . json_encode($this->getSanitizer()->getParam($jsObject, (object) array())) . ';
				graphOptions.colors = ["' . str_replace(',', '","', $this->getOption('defaultColors', self::DEFAULT_COLORS)) . '"];

				' . $jsDataFetch . '
					var data = {data:[], labels:[], legend: []};
					if (typeof prepareJsonData != "undefined") {
						data = prepareJsonData(json, json2);
					} else {
						data.data = json;
					}';

		if ((int)$this->getOption('showLegend') > 0) {
			$javaScriptContent .= '
					if (typeof getLabel != "undefined" && typeof data.legend == "object") {
						for (var i = 0; i < data.legend.length; i++) {
							$("#' . $containerId . '_legend").append("<div style=\'color:" + graphOptions.colors[i%graphOptions.colors.length] + ";\'>" + getLabel(data.legend[i]) + "</div>");
						}
					}';
		}

		$javaScriptContent .= '
					graphOptions.xaxisLabels = data.labels;
					graphOptions.yaxisLabels = data.labels;
					graphOptions.labels = data.labels;
					graphOptions.title = "' . $this->getOption('name') . '";

					var line = new ' . $jsObject . '({
						id: "' . $containerId . '_graph",
						data: data.data,
						options: graphOptions
					}).draw();
				';

		// Load all possible Columns and data from the selected DataSet
		if ($this->getOption('dataSource') == 'dataset') {
			// JSON Data Object
			$graphData = (object)[];
			$graphData->data = [];
			$graphData->labels = [];
			$graphData->legend = [];

			$labelCol = $this->getOption('labelColumn', '');
			$dataSetId = $this->getOption('dataset');
			try {
				$dataSet = $this->dataSetFactory->getById($dataSetId);

				// Get all Headers to show as different Data-Streams
				/* @var DataSetColumn $column */
				foreach ($dataSet->getColumn() as $column) {
					// Only DataSetColumn->dataSetColumnTypeId of "1" (Value) can be processed - "2" (Formula) is not supported
					// Only DataSetColumn->dataTypeId of "2" (Number) or "3" (Date) can be processed - "1" (String), "4" (Ext. Image) and "5" (Library Image) are not supported
					if (($column->dataSetColumnTypeId != 1)
						|| (($column->dataTypeId != 2) && ($column->dataTypeId != 3)) ) {
						continue;
					}
					$graphData->legend[] = $column->heading;
				}
				
				foreach ($dataSet->getData() as $row) {
					// Add the label
					$graphData->labels[] = (strlen($labelCol) > 0) ? $row[$labelCol] : "";
					
					// Add all Data
					foreach ($graphData->legend as $k => $col) {
						$graphData->data[$k][] = $row[$col];
					}
				}
			} catch (\Xibo\Exception\NotFoundException $e) {
				// In case there is a datset to be displayed what does not exists (deleted or so)
				$graphData->data[0][] = 0;
				$graphData->legend[] = 'Unknown Dataset';
				$graphData->labels[] = '';
			}
			
			$javaScriptContent .= '})(' . json_encode($graphData) . ', {});';
		} else {
			$javaScriptContent .= '});';
		}

		if ($closeAdditional) {
			$javaScriptContent .= '});';
		}
		$javaScriptContent .= '
			});</script>';

		// Replace the After body Content
		$data['body'] .= $javaScriptContent;
		//$data['javaScript'] = $javaScriptContent;

		return $this->renderTemplate($data);
	}

	/**
	 * Returns if this module is valid or not.
	 *   0 => Invalid
	 *   1 => Valid
	 *   2 => Unknown
	 * @return Validation-Level
	 * @Override
	 */
	public function IsValid() {
		// Can't be sure because the client does the rendering
		return 2;
	}
}

