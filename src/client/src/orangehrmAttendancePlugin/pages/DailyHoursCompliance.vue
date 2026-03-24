<!--
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
 -->

<template>
  <oxd-table-filter filter-title="Daily 9-Hour Compliance Report">
    <oxd-form @submit-valid="filterItems">
      <oxd-form-row>
        <oxd-grid :cols="4" class="orangehrm-full-width-grid">
          <oxd-grid-item>
            <employee-autocomplete
              v-model="filters.employee"
              :rules="rules.employee"
              :params="{
                includeEmployees: 'currentAndPast',
              }"
            />
          </oxd-grid-item>
          <oxd-grid-item>
            <oxd-input-field
              v-model="filters.month"
              type="select"
              label="Month"
              :options="monthOptions"
              :rules="rules.month"
              required
            />
          </oxd-grid-item>
          <oxd-grid-item>
            <oxd-input-field
              v-model="filters.year"
              type="select"
              label="Year"
              :options="yearOptions"
              :rules="rules.year"
              required
            />
          </oxd-grid-item>
          <oxd-grid-item>
            <date-input
              v-model="filters.date"
              label="Date (Optional)"
              :years="yearArray"
            />
          </oxd-grid-item>
        </oxd-grid>
      </oxd-form-row>

      <oxd-divider />

      <oxd-form-actions>
        <required-text />
        <oxd-button display-type="secondary" label="View" type="submit" />
      </oxd-form-actions>
    </oxd-form>
  </oxd-table-filter>
  <br />
  <div class="orangehrm-paper-container">
    <table-header
      :total="total"
      :selected="0"
      :loading="isLoading"
      :show-divider="false"
    ></table-header>
    <div class="orangehrm-container">
      <oxd-card-table
        :headers="headers"
        :items="items?.data"
        :selectable="false"
        :clickable="false"
        :loading="isLoading"
        row-decorator="oxd-table-decorator-card"
      />
    </div>
    <div class="orangehrm-bottom-container">
      <oxd-pagination
        v-if="showPaginator"
        v-model:current="currentPage"
        :length="pages"
      />
    </div>
  </div>
</template>

<script>
import {computed, ref} from 'vue';
import {
  required,
  shouldNotExceedCharLength,
} from '@/core/util/validation/rules';
import {APIService} from '@/core/util/services/api.service';
import usePaginate from '@ohrm/core/util/composable/usePaginate';
import EmployeeAutocomplete from '@/core/components/inputs/EmployeeAutocomplete';
import useEmployeeNameTranslate from '@/core/util/composable/useEmployeeNameTranslate';
import {yearRange} from '@/core/util/helper/year-range';

export default {
  components: {
    'employee-autocomplete': EmployeeAutocomplete,
  },

  setup() {
    const {$tEmpName} = useEmployeeNameTranslate();

    const now = new Date();
    const currentMonth = now.getMonth() + 1;
    const currentYear = now.getFullYear();

    const monthOptions = [
      {id: 1, label: 'January'},
      {id: 2, label: 'February'},
      {id: 3, label: 'March'},
      {id: 4, label: 'April'},
      {id: 5, label: 'May'},
      {id: 6, label: 'June'},
      {id: 7, label: 'July'},
      {id: 8, label: 'August'},
      {id: 9, label: 'September'},
      {id: 10, label: 'October'},
      {id: 11, label: 'November'},
      {id: 12, label: 'December'},
    ];

    const yearOptions = [];
    for (let y = currentYear; y >= currentYear - 5; y--) {
      yearOptions.push({id: y, label: String(y)});
    }

    const filters = ref({
      employee: null,
      month: monthOptions.find((m) => m.id === currentMonth),
      year: yearOptions.find((y) => y.id === currentYear),
      date: null,
    });

    const rules = {
      employee: [shouldNotExceedCharLength(100)],
      month: [required],
      year: [required],
    };

    const serializedFilters = computed(() => {
      // If a specific date is selected, use it for both fromDate and toDate
      if (filters.value.date) {
        return {
          fromDate: filters.value.date,
          toDate: filters.value.date,
          empNumber: filters.value.employee?.id,
        };
      }
      const month = filters.value.month?.id;
      const year = filters.value.year?.id;
      if (!month || !year) return {};
      const mm = String(month).padStart(2, '0');
      const lastDay = new Date(year, month, 0).getDate();
      return {
        fromDate: `${year}-${mm}-01`,
        toDate: `${year}-${mm}-${String(lastDay).padStart(2, '0')}`,
        empNumber: filters.value.employee?.id,
      };
    });

    const monthNames = [
      '',
      'January',
      'February',
      'March',
      'April',
      'May',
      'June',
      'July',
      'August',
      'September',
      'October',
      'November',
      'December',
    ];

    const normalizer = (data) => {
      return data.map((item) => {
        const status = item.completed9Hours;
        const dateParts = item.date ? item.date.split('-') : [];
        const year = dateParts[0] ?? '';
        const monthNum = parseInt(dateParts[1] ?? '0', 10);
        const day = dateParts[2] ?? '';
        return {
          empNumber: item.empNumber,
          empName: $tEmpName(item, {
            includeMiddle: false,
            excludePastEmpTag: false,
          }),
          month: monthNames[monthNum] || '',
          year: year,
          duration: item.duration?.label ?? '0:00',
          status: status ? 'Present' : 'Absent',
          _cellClasses: {
            status: status
              ? 'compliance-status--present'
              : 'compliance-status--absent',
          },
        };
      });
    };

    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/attendance/employees/daily-hours-compliance',
    );

    const {
      total,
      pages,
      response,
      isLoading,
      execQuery,
      currentPage,
      showPaginator,
    } = usePaginate(http, {
      query: serializedFilters,
      normalizer,
    });

    return {
      http,
      rules,
      total,
      pages,
      filters,
      isLoading,
      execQuery,
      currentPage,
      showPaginator,
      items: response,
      monthOptions,
      yearOptions,
      yearArray: [...yearRange()],
    };
  },

  data() {
    return {
      headers: [
        {
          name: 'empName',
          slot: 'title',
          title: 'Employee Name',
          style: {flex: '25%'},
        },
        {
          name: 'month',
          title: 'Month',
          style: {flex: '15%'},
        },
        {
          name: 'year',
          title: 'Year',
          style: {flex: '10%'},
        },
        {
          name: 'duration',
          title: 'Total Hours',
          style: {flex: '15%'},
        },
        {
          name: 'status',
          title: 'Status (9 Hrs)',
          style: {flex: '15%'},
        },
      ],
    };
  },

  methods: {
    async filterItems() {
      await this.execQuery();
    },
  },
};
</script>

<style lang="scss" scoped>
::v-deep(.compliance-status--present) {
  color: #28a745;
  font-weight: bold;
}
::v-deep(.compliance-status--absent) {
  color: #dc3545;
  font-weight: bold;
}
</style>
