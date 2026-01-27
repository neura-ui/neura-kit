interface Country {
    code: string;
    name: string;
    dialCode: string;
    flag: string;
    pattern?: RegExp;
    format?: string;
    example?: string;
    minLength?: number;
    maxLength?: number;
    nationalPrefix?: string; // Prefix to remove (e.g., '0' for France)
}

interface PhoneInputConfig {
    defaultCountry: string;
    preferredCountries: string[];
    onlyCountries: string[] | null;
    excludeCountries: string[] | null;
    showFlags: boolean;
    showDialCode: boolean;
    searchable: boolean;
    disabled: boolean;
    autoFormat: boolean;
    validateOnBlur: boolean;
    wireProperty: string | null;
}

// Comprehensive country data with validation patterns
const COUNTRIES: Country[] = [
    // North America
    { code: 'US', name: 'United States', dialCode: '1', flag: '🇺🇸', pattern: /^[2-9]\d{2}[2-9]\d{6}$/, format: '(###) ###-####', example: '(201) 555-0123', minLength: 10, maxLength: 10 },
    { code: 'CA', name: 'Canada', dialCode: '1', flag: '🇨🇦', pattern: /^[2-9]\d{2}[2-9]\d{6}$/, format: '(###) ###-####', example: '(416) 555-0123', minLength: 10, maxLength: 10 },
    { code: 'MX', name: 'Mexico', dialCode: '52', flag: '🇲🇽', pattern: /^\d{10}$/, format: '## #### ####', example: '55 1234 5678', minLength: 10, maxLength: 10 },
    
    // Europe
    { code: 'GB', name: 'United Kingdom', dialCode: '44', flag: '🇬🇧', pattern: /^[1-9]\d{9,10}$/, format: '#### ######', example: '7911 123456', minLength: 10, maxLength: 11 },
    { code: 'FR', name: 'France', dialCode: '33', flag: '🇫🇷', pattern: /^[1-9]\d{8}$/, format: '# ## ## ## ##', example: '6 12 34 56 78', minLength: 9, maxLength: 10, nationalPrefix: '0' },
    { code: 'DE', name: 'Germany', dialCode: '49', flag: '🇩🇪', pattern: /^[1-9]\d{6,14}$/, format: '### ########', example: '151 12345678', minLength: 7, maxLength: 15 },
    { code: 'IT', name: 'Italy', dialCode: '39', flag: '🇮🇹', pattern: /^[0-9]\d{5,11}$/, format: '### ### ####', example: '312 345 6789', minLength: 6, maxLength: 12 },
    { code: 'ES', name: 'Spain', dialCode: '34', flag: '🇪🇸', pattern: /^[6-9]\d{8}$/, format: '### ### ###', example: '612 345 678', minLength: 9, maxLength: 9 },
    { code: 'PT', name: 'Portugal', dialCode: '351', flag: '🇵🇹', pattern: /^[1-9]\d{8}$/, format: '### ### ###', example: '912 345 678', minLength: 9, maxLength: 9 },
    { code: 'NL', name: 'Netherlands', dialCode: '31', flag: '🇳🇱', pattern: /^[1-9]\d{8}$/, format: '# ########', example: '6 12345678', minLength: 9, maxLength: 9 },
    { code: 'BE', name: 'Belgium', dialCode: '32', flag: '🇧🇪', pattern: /^[1-9]\d{7,8}$/, format: '### ## ## ##', example: '470 12 34 56', minLength: 8, maxLength: 9 },
    { code: 'CH', name: 'Switzerland', dialCode: '41', flag: '🇨🇭', pattern: /^[1-9]\d{8}$/, format: '## ### ## ##', example: '76 123 45 67', minLength: 9, maxLength: 9 },
    { code: 'AT', name: 'Austria', dialCode: '43', flag: '🇦🇹', pattern: /^[1-9]\d{6,13}$/, format: '### ########', example: '664 1234567', minLength: 7, maxLength: 14 },
    { code: 'PL', name: 'Poland', dialCode: '48', flag: '🇵🇱', pattern: /^[1-9]\d{8}$/, format: '### ### ###', example: '512 345 678', minLength: 9, maxLength: 9 },
    { code: 'SE', name: 'Sweden', dialCode: '46', flag: '🇸🇪', pattern: /^[1-9]\d{6,12}$/, format: '## ### ## ##', example: '70 123 45 67', minLength: 7, maxLength: 13 },
    { code: 'NO', name: 'Norway', dialCode: '47', flag: '🇳🇴', pattern: /^[2-9]\d{7}$/, format: '### ## ###', example: '406 12 345', minLength: 8, maxLength: 8 },
    { code: 'DK', name: 'Denmark', dialCode: '45', flag: '🇩🇰', pattern: /^[2-9]\d{7}$/, format: '## ## ## ##', example: '20 12 34 56', minLength: 8, maxLength: 8 },
    { code: 'FI', name: 'Finland', dialCode: '358', flag: '🇫🇮', pattern: /^[1-9]\d{5,11}$/, format: '## ### ####', example: '40 123 4567', minLength: 6, maxLength: 12 },
    { code: 'IE', name: 'Ireland', dialCode: '353', flag: '🇮🇪', pattern: /^[1-9]\d{6,9}$/, format: '## ### ####', example: '85 123 4567', minLength: 7, maxLength: 10 },
    { code: 'GR', name: 'Greece', dialCode: '30', flag: '🇬🇷', pattern: /^[2-9]\d{9}$/, format: '### ### ####', example: '691 234 5678', minLength: 10, maxLength: 10 },
    { code: 'CZ', name: 'Czech Republic', dialCode: '420', flag: '🇨🇿', pattern: /^[1-9]\d{8}$/, format: '### ### ###', example: '601 234 567', minLength: 9, maxLength: 9 },
    { code: 'RO', name: 'Romania', dialCode: '40', flag: '🇷🇴', pattern: /^[1-9]\d{8}$/, format: '### ### ###', example: '712 345 678', minLength: 9, maxLength: 9 },
    { code: 'HU', name: 'Hungary', dialCode: '36', flag: '🇭🇺', pattern: /^[1-9]\d{8}$/, format: '## ### ####', example: '20 123 4567', minLength: 9, maxLength: 9 },
    { code: 'UA', name: 'Ukraine', dialCode: '380', flag: '🇺🇦', pattern: /^[1-9]\d{8}$/, format: '## ### ####', example: '50 123 4567', minLength: 9, maxLength: 9 },
    { code: 'RU', name: 'Russia', dialCode: '7', flag: '🇷🇺', pattern: /^[1-9]\d{9}$/, format: '### ###-##-##', example: '912 345-67-89', minLength: 10, maxLength: 10 },
    
    // Asia
    { code: 'CN', name: 'China', dialCode: '86', flag: '🇨🇳', pattern: /^1[3-9]\d{9}$/, format: '### #### ####', example: '131 2345 6789', minLength: 11, maxLength: 11 },
    { code: 'JP', name: 'Japan', dialCode: '81', flag: '🇯🇵', pattern: /^[1-9]\d{8,9}$/, format: '##-####-####', example: '90-1234-5678', minLength: 9, maxLength: 10 },
    { code: 'KR', name: 'South Korea', dialCode: '82', flag: '🇰🇷', pattern: /^[1-9]\d{8,9}$/, format: '##-####-####', example: '10-1234-5678', minLength: 9, maxLength: 10 },
    { code: 'IN', name: 'India', dialCode: '91', flag: '🇮🇳', pattern: /^[6-9]\d{9}$/, format: '##### #####', example: '98765 43210', minLength: 10, maxLength: 10 },
    { code: 'PK', name: 'Pakistan', dialCode: '92', flag: '🇵🇰', pattern: /^[1-9]\d{9}$/, format: '### #######', example: '300 1234567', minLength: 10, maxLength: 10 },
    { code: 'BD', name: 'Bangladesh', dialCode: '880', flag: '🇧🇩', pattern: /^1[3-9]\d{8}$/, format: '#### ######', example: '1712 345678', minLength: 10, maxLength: 10 },
    { code: 'ID', name: 'Indonesia', dialCode: '62', flag: '🇮🇩', pattern: /^[1-9]\d{8,11}$/, format: '### #### ####', example: '812 3456 7890', minLength: 9, maxLength: 12 },
    { code: 'MY', name: 'Malaysia', dialCode: '60', flag: '🇲🇾', pattern: /^[1-9]\d{7,9}$/, format: '##-### ####', example: '12-345 6789', minLength: 8, maxLength: 10 },
    { code: 'SG', name: 'Singapore', dialCode: '65', flag: '🇸🇬', pattern: /^[689]\d{7}$/, format: '#### ####', example: '8123 4567', minLength: 8, maxLength: 8 },
    { code: 'TH', name: 'Thailand', dialCode: '66', flag: '🇹🇭', pattern: /^[1-9]\d{8}$/, format: '##-###-####', example: '81-234-5678', minLength: 9, maxLength: 9 },
    { code: 'VN', name: 'Vietnam', dialCode: '84', flag: '🇻🇳', pattern: /^[1-9]\d{8,9}$/, format: '### ### ####', example: '912 345 678', minLength: 9, maxLength: 10 },
    { code: 'PH', name: 'Philippines', dialCode: '63', flag: '🇵🇭', pattern: /^[1-9]\d{9}$/, format: '### ### ####', example: '917 123 4567', minLength: 10, maxLength: 10 },
    { code: 'HK', name: 'Hong Kong', dialCode: '852', flag: '🇭🇰', pattern: /^[2-9]\d{7}$/, format: '#### ####', example: '5123 4567', minLength: 8, maxLength: 8 },
    { code: 'TW', name: 'Taiwan', dialCode: '886', flag: '🇹🇼', pattern: /^[1-9]\d{8}$/, format: '### ### ###', example: '912 345 678', minLength: 9, maxLength: 9 },
    
    // Middle East
    { code: 'AE', name: 'United Arab Emirates', dialCode: '971', flag: '🇦🇪', pattern: /^[1-9]\d{8}$/, format: '## ### ####', example: '50 123 4567', minLength: 9, maxLength: 9 },
    { code: 'SA', name: 'Saudi Arabia', dialCode: '966', flag: '🇸🇦', pattern: /^[1-9]\d{8}$/, format: '## ### ####', example: '50 123 4567', minLength: 9, maxLength: 9 },
    { code: 'IL', name: 'Israel', dialCode: '972', flag: '🇮🇱', pattern: /^[1-9]\d{8}$/, format: '##-###-####', example: '50-123-4567', minLength: 9, maxLength: 9 },
    { code: 'TR', name: 'Turkey', dialCode: '90', flag: '🇹🇷', pattern: /^[1-9]\d{9}$/, format: '### ### ## ##', example: '532 123 45 67', minLength: 10, maxLength: 10 },
    { code: 'QA', name: 'Qatar', dialCode: '974', flag: '🇶🇦', pattern: /^[1-9]\d{7}$/, format: '#### ####', example: '3312 3456', minLength: 8, maxLength: 8 },
    { code: 'KW', name: 'Kuwait', dialCode: '965', flag: '🇰🇼', pattern: /^[1-9]\d{7}$/, format: '#### ####', example: '5000 1234', minLength: 8, maxLength: 8 },
    { code: 'OM', name: 'Oman', dialCode: '968', flag: '🇴🇲', pattern: /^[1-9]\d{7}$/, format: '#### ####', example: '9212 3456', minLength: 8, maxLength: 8 },
    { code: 'BH', name: 'Bahrain', dialCode: '973', flag: '🇧🇭', pattern: /^[1-9]\d{7}$/, format: '#### ####', example: '3600 1234', minLength: 8, maxLength: 8 },
    { code: 'JO', name: 'Jordan', dialCode: '962', flag: '🇯🇴', pattern: /^[1-9]\d{8}$/, format: '# #### ####', example: '7 9012 3456', minLength: 9, maxLength: 9 },
    { code: 'LB', name: 'Lebanon', dialCode: '961', flag: '🇱🇧', pattern: /^[1-9]\d{6,7}$/, format: '## ### ###', example: '71 123 456', minLength: 7, maxLength: 8 },
    
    // Africa
    { code: 'EG', name: 'Egypt', dialCode: '20', flag: '🇪🇬', pattern: /^[1-9]\d{9}$/, format: '### ### ####', example: '100 123 4567', minLength: 10, maxLength: 10 },
    { code: 'ZA', name: 'South Africa', dialCode: '27', flag: '🇿🇦', pattern: /^[1-9]\d{8}$/, format: '## ### ####', example: '71 123 4567', minLength: 9, maxLength: 9 },
    { code: 'NG', name: 'Nigeria', dialCode: '234', flag: '🇳🇬', pattern: /^[1-9]\d{9}$/, format: '### ### ####', example: '802 123 4567', minLength: 10, maxLength: 10 },
    { code: 'KE', name: 'Kenya', dialCode: '254', flag: '🇰🇪', pattern: /^[1-9]\d{8}$/, format: '### ######', example: '712 345678', minLength: 9, maxLength: 9 },
    { code: 'MA', name: 'Morocco', dialCode: '212', flag: '🇲🇦', pattern: /^[1-9]\d{8}$/, format: '### ## ## ##', example: '612 34 56 78', minLength: 9, maxLength: 9 },
    { code: 'TN', name: 'Tunisia', dialCode: '216', flag: '🇹🇳', pattern: /^[1-9]\d{7}$/, format: '## ### ###', example: '20 123 456', minLength: 8, maxLength: 8 },
    { code: 'DZ', name: 'Algeria', dialCode: '213', flag: '🇩🇿', pattern: /^[1-9]\d{8}$/, format: '### ## ## ##', example: '551 23 45 67', minLength: 9, maxLength: 9 },
    { code: 'GH', name: 'Ghana', dialCode: '233', flag: '🇬🇭', pattern: /^[1-9]\d{8}$/, format: '## ### ####', example: '23 123 4567', minLength: 9, maxLength: 9 },
    
    // Oceania
    { code: 'AU', name: 'Australia', dialCode: '61', flag: '🇦🇺', pattern: /^[1-9]\d{8}$/, format: '### ### ###', example: '412 345 678', minLength: 9, maxLength: 9 },
    { code: 'NZ', name: 'New Zealand', dialCode: '64', flag: '🇳🇿', pattern: /^[1-9]\d{7,9}$/, format: '## ### ####', example: '21 123 4567', minLength: 8, maxLength: 10 },
    
    // South America
    { code: 'BR', name: 'Brazil', dialCode: '55', flag: '🇧🇷', pattern: /^[1-9]\d{9,10}$/, format: '## #####-####', example: '11 91234-5678', minLength: 10, maxLength: 11 },
    { code: 'AR', name: 'Argentina', dialCode: '54', flag: '🇦🇷', pattern: /^[1-9]\d{9}$/, format: '## ####-####', example: '11 1234-5678', minLength: 10, maxLength: 10 },
    { code: 'CL', name: 'Chile', dialCode: '56', flag: '🇨🇱', pattern: /^[1-9]\d{8}$/, format: '# #### ####', example: '9 1234 5678', minLength: 9, maxLength: 9 },
    { code: 'CO', name: 'Colombia', dialCode: '57', flag: '🇨🇴', pattern: /^[1-9]\d{9}$/, format: '### ### ####', example: '301 234 5678', minLength: 10, maxLength: 10 },
    { code: 'PE', name: 'Peru', dialCode: '51', flag: '🇵🇪', pattern: /^[1-9]\d{8}$/, format: '### ### ###', example: '912 345 678', minLength: 9, maxLength: 9 },
    { code: 'VE', name: 'Venezuela', dialCode: '58', flag: '🇻🇪', pattern: /^[1-9]\d{9}$/, format: '### ###-####', example: '412 123-4567', minLength: 10, maxLength: 10 },
    { code: 'EC', name: 'Ecuador', dialCode: '593', flag: '🇪🇨', pattern: /^[1-9]\d{8}$/, format: '## ### ####', example: '99 123 4567', minLength: 9, maxLength: 9 },
    { code: 'UY', name: 'Uruguay', dialCode: '598', flag: '🇺🇾', pattern: /^[1-9]\d{7}$/, format: '#### ####', example: '9412 3456', minLength: 8, maxLength: 8 },
    { code: 'PY', name: 'Paraguay', dialCode: '595', flag: '🇵🇾', pattern: /^[1-9]\d{8}$/, format: '### ######', example: '961 456789', minLength: 9, maxLength: 9 },
    { code: 'BO', name: 'Bolivia', dialCode: '591', flag: '🇧🇴', pattern: /^[1-9]\d{7}$/, format: '# #######', example: '7 1234567', minLength: 8, maxLength: 8 },
];

function neuraPhoneInput(config: PhoneInputConfig) {
    return {
        // State
        open: false,
        search: '',
        nationalNumber: '',
        selectedCountry: null as Country | null,
        focusedIndex: 0,
        isValid: false,
        validationMessage: '',
        touched: false,
        _lastSyncedValue: '', // Track last synced value to prevent loops
        
        // Config
        isDisabled: config.disabled,
        autoFormat: config.autoFormat,
        validateOnBlur: config.validateOnBlur,
        wireProperty: config.wireProperty,
        preferredCountryCodes: config.preferredCountries || [],
        
        // Computed
        get countries(): Country[] {
            let list = [...COUNTRIES];
            
            if (config.onlyCountries && config.onlyCountries.length > 0) {
                list = list.filter(c => config.onlyCountries!.includes(c.code));
            }
            
            if (config.excludeCountries && config.excludeCountries.length > 0) {
                list = list.filter(c => !config.excludeCountries!.includes(c.code));
            }
            
            return list.sort((a, b) => a.name.localeCompare(b.name));
        },
        
        get preferredCountriesList(): Country[] {
            return this.preferredCountryCodes
                .map(code => this.countries.find(c => c.code === code))
                .filter((c): c is Country => c !== undefined);
        },
        
        get filteredCountries(): Country[] {
            if (!this.search.trim()) {
                // Exclude preferred countries from main list when showing preferred section
                return this.countries.filter(c => !this.preferredCountryCodes.includes(c.code));
            }
            
            const term = this.search.toLowerCase().trim();
            return this.countries.filter(c => 
                c.name.toLowerCase().includes(term) ||
                c.dialCode.includes(term) ||
                c.code.toLowerCase().includes(term)
            );
        },
        
        get fullNumber(): string {
            if (!this.selectedCountry || !this.nationalNumber) return '';
            const cleanNumber = this.nationalNumber.replace(/\D/g, '');
            return `+${this.selectedCountry.dialCode}${cleanNumber}`;
        },
        
        get placeholder(): string {
            return this.selectedCountry?.example || '';
        },
        
        // Methods
        init() {
            // Set default country
            const defaultCountry = this.countries.find(c => c.code === config.defaultCountry);
            if (defaultCountry) {
                this.selectedCountry = defaultCountry;
            }
            
            // Initialize from wire model if exists
            if (this.wireProperty && this.$wire) {
                const value = this.$wire.get(this.wireProperty);
                if (value) {
                    this._lastSyncedValue = value;
                    this.parseFullNumber(value);
                }
                
                // Watch for EXTERNAL changes only (not our own updates)
                this.$watch(() => this.$wire.get(this.wireProperty), (newValue: string) => {
                    // Skip if this is the value we just sent
                    if (newValue === this._lastSyncedValue) return;
                    
                    // Only parse if the cleaned digits are different
                    const newDigits = (newValue || '').replace(/\D/g, '');
                    const currentDigits = this.fullNumber.replace(/\D/g, '');
                    
                    if (newDigits !== currentDigits) {
                        this._lastSyncedValue = newValue;
                        this.parseFullNumber(newValue);
                    }
                });
            }
        },
        
        parseFullNumber(fullNumber: string) {
            if (!fullNumber) return;
            
            // Remove all non-digit characters except +
            const cleaned = fullNumber.replace(/[^\d+]/g, '');
            
            if (cleaned.startsWith('+')) {
                // Try to match country by dial code
                const withoutPlus = cleaned.substring(1);
                
                // Try different dial code lengths (1-4 digits)
                for (let len = 4; len >= 1; len--) {
                    const dialCode = withoutPlus.substring(0, len);
                    const country = this.countries.find(c => c.dialCode === dialCode);
                    
                    if (country) {
                        this.selectedCountry = country;
                        this.nationalNumber = withoutPlus.substring(len);
                        if (this.autoFormat) {
                            this.formatNumber();
                        }
                        return;
                    }
                }
            }
            
            // If no country found, just set the number
            this.nationalNumber = cleaned.replace(/^\+/, '');
        },
        
        toggleDropdown() {
            if (this.isDisabled) return;
            
            this.open = !this.open;
            this.search = '';
            this.focusedIndex = 0;
            
            if (this.open && config.searchable) {
                this.$nextTick(() => {
                    this.$refs.searchInput?.focus();
                });
            }
        },
        
        closeDropdown() {
            this.open = false;
            this.search = '';
        },
        
        selectCountry(country: Country) {
            this.selectedCountry = country;
            this.closeDropdown();
            this.$refs.phoneInput?.focus();
            this.validate();
            this.syncToWire();
        },
        
        getCountryIndex(code: string): number {
            const prefIndex = this.preferredCountriesList.findIndex(c => c.code === code);
            if (prefIndex >= 0 && !this.search) return prefIndex;
            
            const mainIndex = this.filteredCountries.findIndex(c => c.code === code);
            return this.search ? mainIndex : mainIndex + this.preferredCountriesList.length;
        },
        
        focusNext() {
            const total = this.search 
                ? this.filteredCountries.length 
                : this.preferredCountriesList.length + this.filteredCountries.length;
            
            this.focusedIndex = (this.focusedIndex + 1) % total;
        },
        
        focusPrev() {
            const total = this.search 
                ? this.filteredCountries.length 
                : this.preferredCountriesList.length + this.filteredCountries.length;
            
            this.focusedIndex = (this.focusedIndex - 1 + total) % total;
        },
        
        selectFocused() {
            const list = this.search 
                ? this.filteredCountries 
                : [...this.preferredCountriesList, ...this.filteredCountries];
            
            const country = list[this.focusedIndex];
            if (country) {
                this.selectCountry(country);
            }
        },
        
        handleInput(event: Event) {
            const input = event.target as HTMLInputElement;
            let value = input.value;
            
            // Allow only digits and formatting characters
            value = value.replace(/[^\d\s\-().]/g, '');
            
            // Remove national prefix if present (e.g., leading 0 for France)
            if (this.selectedCountry?.nationalPrefix) {
                const digits = value.replace(/\D/g, '');
                if (digits.startsWith(this.selectedCountry.nationalPrefix)) {
                    value = digits.substring(this.selectedCountry.nationalPrefix.length);
                }
            }
            
            this.nationalNumber = value;
            
            if (this.autoFormat) {
                this.$nextTick(() => {
                    this.formatNumber();
                });
            }
            
            this.syncToWire();
        },
        
        handleBlur() {
            this.touched = true;
            
            if (this.autoFormat) {
                this.formatNumber();
            }
            
            if (this.validateOnBlur) {
                this.validate();
            }
            
            this.syncToWire();
        },
        
        formatNumber() {
            if (!this.selectedCountry?.format || !this.nationalNumber) return;
            
            const digits = this.nationalNumber.replace(/\D/g, '');
            const format = this.selectedCountry.format;
            
            let formatted = '';
            let digitIndex = 0;
            
            for (const char of format) {
                if (digitIndex >= digits.length) break;
                
                if (char === '#') {
                    formatted += digits[digitIndex];
                    digitIndex++;
                } else {
                    formatted += char;
                }
            }
            
            // Add remaining digits if format is shorter
            if (digitIndex < digits.length) {
                formatted += digits.substring(digitIndex);
            }
            
            this.nationalNumber = formatted.trim();
        },
        
        validate(): boolean {
            if (!this.selectedCountry) {
                this.isValid = false;
                this.validationMessage = '';
                return false;
            }
            
            let digits = this.nationalNumber.replace(/\D/g, '');
            
            if (!digits) {
                this.isValid = false;
                this.validationMessage = '';
                return false;
            }
            
            // Remove national prefix for validation if present
            if (this.selectedCountry.nationalPrefix && digits.startsWith(this.selectedCountry.nationalPrefix)) {
                digits = digits.substring(this.selectedCountry.nationalPrefix.length);
            }
            
            const { pattern, minLength, maxLength, name } = this.selectedCountry;
            
            // Check length
            if (minLength && digits.length < minLength) {
                this.isValid = false;
                this.validationMessage = `Phone number is too short for ${name}`;
                return false;
            }
            
            if (maxLength && digits.length > maxLength) {
                this.isValid = false;
                this.validationMessage = `Phone number is too long for ${name}`;
                return false;
            }
            
            // Check pattern
            if (pattern && !pattern.test(digits)) {
                this.isValid = false;
                this.validationMessage = `Invalid phone number format for ${name}`;
                return false;
            }
            
            this.isValid = true;
            this.validationMessage = 'Valid phone number';
            return true;
        },
        
        syncToWire() {
            if (this.wireProperty && this.$wire) {
                const value = this.fullNumber;
                // Only sync if value actually changed
                if (value !== this._lastSyncedValue) {
                    this._lastSyncedValue = value;
                    this.$wire.set(this.wireProperty, value);
                }
            }
        },
        
        // Allow external access
        getFullNumber(): string {
            return this.fullNumber;
        },
        
        isValidNumber(): boolean {
            return this.validate();
        },
        
        getCountryCode(): string {
            return this.selectedCountry?.code || '';
        },
        
        getDialCode(): string {
            return this.selectedCountry?.dialCode || '';
        },
        
        getNationalNumber(): string {
            return this.nationalNumber.replace(/\D/g, '');
        },
    };
}

// Register globally for Alpine.js
if (typeof window !== 'undefined') {
    (window as any).neuraPhoneInput = neuraPhoneInput;
}

export { neuraPhoneInput, COUNTRIES };
export type { Country, PhoneInputConfig };
