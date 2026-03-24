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

namespace OrangeHRM\Attendance\Api;

use DateTime;
use OrangeHRM\Attendance\Api\Model\EmployeeDailyHoursComplianceListModel;
use OrangeHRM\Attendance\Traits\Service\AttendanceServiceTrait;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CollectionEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Service\DateTimeHelperTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\Employee;

/**
 * API to get daily 9-hour compliance report for employees.
 * Groups attendance per employee per day, excludes Saturdays and Sundays,
 * and indicates whether each employee completed 9 hours on each working day.
 */
class EmployeeDailyHoursComplianceAPI extends Endpoint implements CollectionEndpoint
{
    use UserRoleManagerTrait;
    use AttendanceServiceTrait;
    use DateTimeHelperTrait;

    public const FILTER_FROM_DATE = 'fromDate';
    public const FILTER_TO_DATE = 'toDate';
    public const PARAMETER_EMP_NUMBER = 'empNumber';

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $fromDate = $this->getRequestParams()->getDateTimeOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::FILTER_FROM_DATE
        );

        $toDate = $this->getRequestParams()->getDateTimeOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::FILTER_TO_DATE
        );

        $employeeNumber = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_EMP_NUMBER
        );

        // Default to today if no dates provided
        if ($fromDate === null && $toDate === null) {
            $today = $this->getDateTimeHelper()->getNow()->format('Y-m-d');
            $fromDate = new DateTime($today . ' 00:00:00');
            $toDate = new DateTime($today . ' 23:59:59');
        } else {
            if (!$fromDate instanceof DateTime || !$toDate instanceof DateTime) {
                throw $this->getInvalidParamException([self::FILTER_FROM_DATE, self::FILTER_TO_DATE]);
            }
            if ($fromDate > $toDate) {
                throw $this->getInvalidParamException([self::FILTER_FROM_DATE, self::FILTER_TO_DATE]);
            }
            $fromDate = new DateTime($fromDate->format('Y-m-d') . ' 00:00:00');
            $toDate = new DateTime($toDate->format('Y-m-d') . ' 23:59:59');
        }

        // Get accessible employee IDs (Admin sees all, Supervisor sees subordinates)
        if (!is_null($employeeNumber)) {
            $empNumbers = [$employeeNumber];
        } else {
            $empNumbers = $this->getUserRoleManager()->getAccessibleEntityIds(Employee::class);
        }

        $records = $this->getAttendanceService()
            ->getAttendanceDao()
            ->getEmployeeDailyHoursCompliance($fromDate, $toDate, $empNumbers);

        // Filter out Saturdays (6) and Sundays (7)
        $filteredRecords = array_values(array_filter($records, function ($record) {
            $dayOfWeek = (int)(new DateTime($record['workDate']))->format('N'); // 1=Mon ... 7=Sun
            return $dayOfWeek < 6;
        }));

        return new EndpointCollectionResult(
            EmployeeDailyHoursComplianceListModel::class,
            [$filteredRecords],
            new ParameterBag([
                CommonParams::PARAMETER_TOTAL => count($filteredRecords),
            ])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::FILTER_FROM_DATE,
                    new Rule(Rules::API_DATE)
                ),
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::FILTER_TO_DATE,
                    new Rule(Rules::API_DATE)
                ),
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_EMP_NUMBER,
                    new Rule(Rules::POSITIVE),
                    new Rule(Rules::ENTITY_ID_EXISTS, [Employee::class]),
                    new Rule(Rules::IN_ACCESSIBLE_ENTITY_ID, [Employee::class])
                ),
            ),
            ...$this->getSortingAndPaginationParamsRules()
        );
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
