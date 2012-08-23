<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>MPASM{if $routinename} - {$routinename}{/if}</title>
		<script type="text/javascript" src="/jsonreport.js"></script>
		<script type="text/javascript">
			function onLoad() {
				document.getElementById('report').innerHTML = _.jsonreport('{json($data)}');
			}
		</script>
	</head>
<body onload="onLoad();">
	<div class="jsonreport" id="report"></div>
</body>
</html>