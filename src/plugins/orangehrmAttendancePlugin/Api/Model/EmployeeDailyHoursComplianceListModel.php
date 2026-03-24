<?php

/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */

namespace OrangeHRM\Attendance\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\CollectionNormalizable;
use OrangeHRM\Core\Api\V2\Serializer\ModelConstructorArgsAwareInterface;
use OrangeHRM\Core\Traits\Service\NumberHelperTrait;

class EmployeeDailyHoursComplianceListModel implements CollectionNormalizable, ModelConstructorArgsAwareInterface
{
    use NumberHelperTrait;

    private const REQUIRED_SECONDS = 32400; // 9 hours

    private array $records;

    public function __construct(array $records)
    {
        $this->records = $records;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->records as $record) {
            $totalSeconds = (float)($record['total'] ?? 0);
            $result[] = [
                'empNumber' => $record['empNumber'],
                'lastName' => $record['lastName'],
                'firstName' => $record['firstName'],
                'middleName' => $record['middleName'],
                'employeeId' => $record['employeeId'],
                'terminationId' => $record['terminationId'],
                'date' => $record['workDate'],
                'duration' => [
                    'hours' => floor($totalSeconds / 3600),
                    'minutes' => ((int)($totalSeconds / 60)) % 60,
                    'label' => $this->getNumberHelper()->numberFormat($totalSeconds / 3600, 2),
                ],
                'completed9Hours' => $totalSeconds >= self::REQUIRED_SECONDS,
            ];
        }
        return $result;
    }
}
