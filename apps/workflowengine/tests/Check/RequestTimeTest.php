<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Tests\Check;

use OCA\WorkflowEngine\Check\RequestTime;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IL10N;

class RequestTimeTest extends \Test\TestCase {

	/** @var ITimeFactory|\PHPUnit\Framework\MockObject\MockObject */
	protected $timeFactory;

	/**
	 * @return IL10N|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected function getL10NMock() {
		$l = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()
			->getMock();
		$l->expects($this->any())
			->method('t')
			->willReturnCallback(function ($string, $args) {
				return sprintf($string, $args);
			});
		return $l;
	}

	protected function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->getMockBuilder('OCP\AppFramework\Utility\ITimeFactory')
			->getMock();
	}

	public function dataExecuteCheck() {
		return [
			[json_encode(['08:00 Europe/Berlin', '17:00 Europe/Berlin']), 1467870105, false], // 2016-07-07T07:41:45+02:00
			[json_encode(['08:00 Europe/Berlin', '17:00 Europe/Berlin']), 1467873705, true], // 2016-07-07T08:41:45+02:00
			[json_encode(['08:00 Europe/Berlin', '17:00 Europe/Berlin']), 1467902505, true], // 2016-07-07T16:41:45+02:00
			[json_encode(['08:00 Europe/Berlin', '17:00 Europe/Berlin']), 1467906105, false], // 2016-07-07T17:41:45+02:00
			[json_encode(['17:00 Europe/Berlin', '08:00 Europe/Berlin']), 1467870105, true], // 2016-07-07T07:41:45+02:00
			[json_encode(['17:00 Europe/Berlin', '08:00 Europe/Berlin']), 1467873705, false], // 2016-07-07T08:41:45+02:00
			[json_encode(['17:00 Europe/Berlin', '08:00 Europe/Berlin']), 1467902505, false], // 2016-07-07T16:41:45+02:00
			[json_encode(['17:00 Europe/Berlin', '08:00 Europe/Berlin']), 1467906105, true], // 2016-07-07T17:41:45+02:00

			[json_encode(['08:00 Australia/Adelaide', '17:00 Australia/Adelaide']), 1467843105, false], // 2016-07-07T07:41:45+09:30
			[json_encode(['08:00 Australia/Adelaide', '17:00 Australia/Adelaide']), 1467846705, true], // 2016-07-07T08:41:45+09:30
			[json_encode(['08:00 Australia/Adelaide', '17:00 Australia/Adelaide']), 1467875505, true], // 2016-07-07T16:41:45+09:30
			[json_encode(['08:00 Australia/Adelaide', '17:00 Australia/Adelaide']), 1467879105, false], // 2016-07-07T17:41:45+09:30
			[json_encode(['17:00 Australia/Adelaide', '08:00 Australia/Adelaide']), 1467843105, true], // 2016-07-07T07:41:45+09:30
			[json_encode(['17:00 Australia/Adelaide', '08:00 Australia/Adelaide']), 1467846705, false], // 2016-07-07T08:41:45+09:30
			[json_encode(['17:00 Australia/Adelaide', '08:00 Australia/Adelaide']), 1467875505, false], // 2016-07-07T16:41:45+09:30
			[json_encode(['17:00 Australia/Adelaide', '08:00 Australia/Adelaide']), 1467879105, true], // 2016-07-07T17:41:45+09:30

			[json_encode(['08:00 Pacific/Niue', '17:00 Pacific/Niue']), 1467916905, false], // 2016-07-07T07:41:45-11:00
			[json_encode(['08:00 Pacific/Niue', '17:00 Pacific/Niue']), 1467920505, true], // 2016-07-07T08:41:45-11:00
			[json_encode(['08:00 Pacific/Niue', '17:00 Pacific/Niue']), 1467949305, true], // 2016-07-07T16:41:45-11:00
			[json_encode(['08:00 Pacific/Niue', '17:00 Pacific/Niue']), 1467952905, false], // 2016-07-07T17:41:45-11:00
			[json_encode(['17:00 Pacific/Niue', '08:00 Pacific/Niue']), 1467916905, true], // 2016-07-07T07:41:45-11:00
			[json_encode(['17:00 Pacific/Niue', '08:00 Pacific/Niue']), 1467920505, false], // 2016-07-07T08:41:45-11:00
			[json_encode(['17:00 Pacific/Niue', '08:00 Pacific/Niue']), 1467949305, false], // 2016-07-07T16:41:45-11:00
			[json_encode(['17:00 Pacific/Niue', '08:00 Pacific/Niue']), 1467952905, true], // 2016-07-07T17:41:45-11:00
		];
	}

	/**
	 * @dataProvider dataExecuteCheck
	 * @param string $value
	 * @param int $timestamp
	 * @param bool $expected
	 */
	public function testExecuteCheckIn($value, $timestamp, $expected): void {
		$check = new RequestTime($this->getL10NMock(), $this->timeFactory);

		$this->timeFactory->expects($this->once())
			->method('getTime')
			->willReturn($timestamp);

		$this->assertEquals($expected, $check->executeCheck('in', $value));
	}

	/**
	 * @dataProvider dataExecuteCheck
	 * @param string $value
	 * @param int $timestamp
	 * @param bool $expected
	 */
	public function testExecuteCheckNotIn($value, $timestamp, $expected): void {
		$check = new RequestTime($this->getL10NMock(), $this->timeFactory);

		$this->timeFactory->expects($this->once())
			->method('getTime')
			->willReturn($timestamp);

		$this->assertEquals(!$expected, $check->executeCheck('!in', $value));
	}

	public function dataValidateCheck() {
		return [
			['in', '["08:00 Europe/Berlin","17:00 Europe/Berlin"]'],
			['!in', '["08:00 Europe/Berlin","17:00 America/North_Dakota/Beulah"]'],
			['in', '["08:00 America/Port-au-Prince","17:00 America/Argentina/San_Luis"]'],
		];
	}

	/**
	 * @dataProvider dataValidateCheck
	 * @param string $operator
	 * @param string $value
	 */
	public function testValidateCheck($operator, $value): void {
		$check = new RequestTime($this->getL10NMock(), $this->timeFactory);
		$check->validateCheck($operator, $value);
		$this->addToAssertionCount(1);
	}

	public function dataValidateCheckInvalid() {
		return [
			['!!in', '["08:00 Europe/Berlin","17:00 Europe/Berlin"]', 1, 'The given operator is invalid'],
			['in', '["28:00 Europe/Berlin","17:00 Europe/Berlin"]', 2, 'The given time span is invalid'],
			['in', '["08:00 Europe/Berlin","27:00 Europe/Berlin"]', 2, 'The given time span is invalid'],
			['in', '["08:00 Europa/Berlin","17:00 Europe/Berlin"]', 3, 'The given start time is invalid'],
			['in', '["08:00 Europe/Berlin","17:00 Europa/Berlin"]', 4, 'The given end time is invalid'],
			['in', '["08:00 Europe/Bearlin","17:00 Europe/Berlin"]', 3, 'The given start time is invalid'],
			['in', '["08:00 Europe/Berlin","17:00 Europe/Bearlin"]', 4, 'The given end time is invalid'],
		];
	}

	/**
	 * @dataProvider dataValidateCheckInvalid
	 * @param string $operator
	 * @param string $value
	 * @param int $exceptionCode
	 * @param string $exceptionMessage
	 */
	public function testValidateCheckInvalid($operator, $value, $exceptionCode, $exceptionMessage): void {
		$check = new RequestTime($this->getL10NMock(), $this->timeFactory);

		try {
			$check->validateCheck($operator, $value);
		} catch (\UnexpectedValueException $e) {
			$this->assertEquals($exceptionCode, $e->getCode());
			$this->assertEquals($exceptionMessage, $e->getMessage());
		}
	}
}
