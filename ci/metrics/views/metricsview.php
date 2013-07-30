<html>
<head>
<title>OLAC Metrics</title>
<link rel="stylesheet" type="text/css" href="/js/yui/build/button/assets/skins/sam/button.css"></link>
<link rel="stylesheet" type="text/css" href="/js/yui/build/menu/assets/skins/sam/menu.css"></link>
<link rel="stylesheet" type="text/css" href="/js/yui/build/datatable/assets/skins/sam/datatable.css"></link>
<link rel="stylesheet" type="text/css" href="/js/yui/build/tabview/assets/skins/sam/tabview.css"></link>
<link rel="stylesheet" type="text/css" href="/js/yui/build/container/assets/container.css"></link>
<script src="/js/yui/build/yahoo-dom-event/yahoo-dom-event.js"></script>
<script src="/js/yui/build/container/container_core-min.js"></script>
<script src="/js/yui/build/yahoo/yahoo-min.js"></script>
<script src="/js/yui/build/event/event-min.js"></script>
<script src="/js/yui/build/menu/menu-min.js"></script>
<script src="/js/yui/build/connection/connection-min.js"></script>
<script src="/js/yui/build/element/element-beta-min.js"></script>
<script src="/js/yui/build/datasource/datasource-beta-min.js"></script>
<script src="/js/yui/build/datatable/datatable-beta-min.js"></script>
<script src="/js/yui/build/container/container-min.js"></script>
<script src="/js/yui/build/tabview/tabview-min.js"></script>
<script src="/js/yui/build/animation/animation-min.js"></script>
<script src="/js/yui/build/dragdrop/dragdrop-min.js"></script>
<script src="/js/yui/build/container/container-min.js"></script>
<script src="/js/yui/build/button/button-min.js"></script>
<script>
<?php
echo "baseurl = '/metrics';\n";
readfile(APPPATH."/views/metrics.js");
if (isset($error))
{
	echo <<<EOF
alert("ERROR: $error");
EOF;
}
?>
</script>
<style type="text/css">
.yui-dt-col-value .yui-dt-last {
	text-align: center;
}
</style>
</head>
<?php
$tab1cls = 'class="selected"';
$tab2cls = '';
if (isset($comparative) && $comparative)
{
	$tab1cls = '';
	$tab2cls = 'class="selected"';
}
?>
<body class="yui-skin-sam">

<div id="tabview" class="yui-navset">
	<ul class="yui-nav">
		<li <?=$tab1cls?>><a href="#tab1"><em>OLAC Archive Metrics</em></a></li>
		<li <?=$tab2cls?>><a href="#tab2"><em>Comparative Archive Metrics</em></a></li>
	</ul>
	<div class="yui-content">
		<div>

			<form>
			<?php
			if (!isset($defaultArchiveId)) $defaultArchiveId=-1;
			?>
			<input type="hidden" id="defaultArchiveId" value="<?=$defaultArchiveId;?>"></input>
			<select id="archivelist">
			</select>
			</form>

			<h1>Summary Statistics</h1>
			<div id="sumstat"></div>

                        <div id="note" style="font-size:small">Note: Record views and click-throughs are for the month of <span id="pvdate"></span>.</div><br>
			<a href="http://www.language-archives.org/NOTE/metrics.html">Explanation of Metrics</a><br><br>

			<h1>Metadata Usage</h1>
			<h3>Core Components</h3>
			<div id="corecomp"></div>
			<h3>Element Usage</h3>
			<div id="elemuse"></div>
			<h3>Refinement Usage</h3>
			<div id="refineuse"></div>
			<h3>Encoding Scheme Usage</h3>
			<div id="encschm"></div>

		</div>
		<div id="comparativeTab">
			<!-- <button id="configure">Configure</button> -->
			<p>(Click column headers to sort)</p>
			<div id="table"></div>
			<!--
			<div id="comparativePanel">
				<div class="hd">Configure Fields</div>
				<div class="bd">
					<div id="configurationGroup">
						<input type="checkbox" id="chkNumRes" value="Number of resources"/>
						<input type="checkbox" id="chkNumLang" value="Distinct languages"/>
						<input type="checkbox" id="chkNumLangFields" value="Distinct linguistic subfields"/>
						<input type="checkbox" id="chkNumLangTypes" value="Distinct linguistic types"/>
						<input type="checkbox" id="chkNumDcmi" value="Distinct DCMI types"/>
						<input type="checkbox" id="chkScore" value="Average metadata quality score"/>
						<input type="checkbox" id="chkNumElements" value="Average elements per record"/>
					</div>
				</div>
				<div class="ft"></div>
			</div>
			-->
		</div>
	</div>

</body>
</html>
