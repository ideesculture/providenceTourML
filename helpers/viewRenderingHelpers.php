<?php	
	/**
	 * HTML vardump : useful for debugging
	 * @param unknown_type $var
	 */
	function htmlvardump($var) {
		echo '<pre>'; // This is for correct handling of newlines
		ob_start();
		var_dump($var);
		$a=ob_get_contents();
		ob_end_clean();
		echo htmlspecialchars($a,ENT_QUOTES); // Escape every HTML special chars (especially > and < )
		echo '</pre>';		
	}
