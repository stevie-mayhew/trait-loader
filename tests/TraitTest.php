<?php

namespace sgn;

class TraitTest extends \SapphireTest {
	use TestTrait, \sgn_TestTrait;

	public function testNamespacedTrait() {
		$this->assertEquals('sgn', $this->namespaceTest());
	}

	public function testGlobalTrait() {
		$this->assertEquals('global', $this->globalTest());
	}
}
