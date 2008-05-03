<?php

/**
 * Class abstraction for language code generation maintenance scripts.
 *
 * @addtogroup Extensions
 */

abstract class GenerateLanguageCodes {

	final public function __construct( $input, $output ) {

		file_put_contents( $output, $this->prepare( $this->parse( file_get_contents( $input ) ) ) );

	}

	final protected function prepare( $codes ) {

		$export = var_export( $codes, true );

		return <<<HEREDOC
<?php

/**
 * Language codes file.
 *
 * @addtogroup Extensions
 */

\$codes = $export;

HEREDOC;

	}

	abstract protected function parse( $file );

}