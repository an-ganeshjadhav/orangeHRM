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

namespace OrangeHRM\Core\Api\V2\Validator\Rules;

use DateTimeZone;
use Exception;

class TimezoneName extends AbstractRule
{
    /**
     * Map of deprecated IANA timezone names to their modern equivalents.
     */
    private const DEPRECATED_TIMEZONES = [
        'Asia/Calcutta' => 'Asia/Kolkata',
        'Asia/Saigon' => 'Asia/Ho_Chi_Minh',
        'Asia/Katmandu' => 'Asia/Kathmandu',
        'Asia/Rangoon' => 'Asia/Yangon',
        'Asia/Thimbu' => 'Asia/Thimphu',
        'Asia/Ujung_Pandang' => 'Asia/Makassar',
        'Asia/Ulan_Bator' => 'Asia/Ulaanbaatar',
        'Asia/Dacca' => 'Asia/Dhaka',
        'Asia/Ashkhabad' => 'Asia/Ashgabat',
        'Asia/Muscat' => 'Asia/Dubai',
        'Pacific/Ponape' => 'Pacific/Pohnpei',
        'Pacific/Truk' => 'Pacific/Chuuk',
        'Pacific/Samoa' => 'Pacific/Pago_Pago',
        'Atlantic/Faeroe' => 'Atlantic/Faroe',
        'Europe/Kiev' => 'Europe/Kyiv',
        'America/Buenos_Aires' => 'America/Argentina/Buenos_Aires',
        'America/Indianapolis' => 'America/Indiana/Indianapolis',
        'America/Louisville' => 'America/Kentucky/Louisville',
        'America/Catamarca' => 'America/Argentina/Catamarca',
        'America/Cordoba' => 'America/Argentina/Cordoba',
        'America/Mendoza' => 'America/Argentina/Mendoza',
    ];

    /**
     * @inheritDoc
     */
    public function validate($input): bool
    {
        if (!(is_string($input) && $this->isValidTimezone($input))) {
            return false;
        }
        return true;
    }

    /**
     * @param string $timezoneName
     * @return bool
     */
    private function isValidTimezone(string $timezoneName): bool
    {
        // Resolve deprecated timezone names to modern equivalents
        $resolved = self::DEPRECATED_TIMEZONES[$timezoneName] ?? $timezoneName;
        try {
            new DateTimeZone($resolved);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
