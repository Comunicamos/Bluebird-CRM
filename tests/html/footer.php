
<div id="footer">

</div>

</div> <!-- wrap -->

<script type="text/javascript">

	$(document).ready(function(){
		$("#envir-select").val('<?php echo $envir;?>');
		$("#envir-select option:selected").trigger('click');
		$("#host-list").val('<?php echo $host;?>');
	});
	
	function changed(id) {
		$("#host-list option").remove();
		<?php
			foreach ($list as $id => $l) {
				echo "if (id == '$id') {\n";
				foreach ($l as $ls) {
					echo "$(\"#host-list\").append(\"<option value='$ls'>$ls</option>\"); \n";
				}
				echo "}\n";				
			}
		?>
	}

</script>

</body>
</html>