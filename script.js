// Course-Stream-Specialization mapping
const courseStreamMap = {
    // Diploma
    "Mechanical Engineering": ["Production", "Automobile", "Mechatronics", "Tool Engineering"],
    "Civil Engineering": ["Construction", "Environmental", "Structural", "Transportation"],
    "Electrical Engineering": ["Power Systems", "Control Systems", "Instrumentation"],
    "Electronics Engineering": ["Telecommunication", "Embedded Systems", "VLSI"],
    "Computer Science Engineering": ["AI & ML", "Cyber Security", "Cloud Computing", "Data Science"],
    "Information Science": ["Networking", "Database Management", "Web Technologies"],
    "Automobile Engineering": ["Automotive Design", "Vehicle Dynamics", "Alternative Fuels"],
    "Chemical Engineering": ["Petrochemical", "Polymer", "Process Control"],
    "Biotechnology": ["Medical Biotechnology", "Industrial Biotechnology", "Bioinformatics"],
    "Architecture": ["Urban Design", "Landscape Architecture", "Sustainable Design"],
    "Pharmacy": ["Pharmaceutics", "Pharmacology", "Pharmacognosy"],
    
    // Degree
    "B.Tech": ["Computer Science", "Electronics", "Mechanical", "Civil", "Information Science"],
    "B.E": ["Computer Science", "Electronics", "Mechanical", "Civil", "Electrical"],
    "B.Sc": ["Physics", "Chemistry", "Mathematics", "Biotechnology", "Computer Science"],
    "B.Com": ["Accounting", "Finance", "Taxation", "Banking", "E-Commerce"],
    "BBA": ["Marketing", "Finance", "HR", "International Business", "Entrepreneurship"],
    "BA": ["Economics", "Psychology", "Sociology", "History", "Political Science"],
    "B.Arch": ["Sustainable Architecture", "Urban Design", "Landscape Architecture"],
    "B.Pharm": ["Pharmaceutics", "Pharmacology", "Pharmacognosy"],
    "BHM": ["Food Production", "Front Office", "Housekeeping", "Food & Beverage"],
    "BCA": ["Cyber Security", "Data Science", "AI & Machine Learning", "Cloud Computing", "Cloud and Mobile Computing", "DevOps"],
    
    // Post Graduate
    "M.Tech": ["Computer Science", "VLSI", "Power Systems", "Structural Engineering"],
    "MBA": ["Finance", "Marketing", "Human Resource Management", "Operations Management", "Information Technology / Systems", "International Business", "Supply Chain Management", "Entrepreneurship & Innovation", "Business Analytics / Data Analytics"],
    "M.Sc": ["Physics", "Chemistry", "Mathematics", "Biotechnology"],
    "M.Com": ["Accounting", "Finance", "Business Studies"],
    "MCA": ["Cyber Security", "Data Science", "AI & Machine Learning", "Cloud Computing", "Cloud and Mobile Computing", "DevOps"],
    "MA": ["English", "Economics", "History", "Sociology"],
    "M.Arch": ["Urban Design", "Landscape Architecture", "Sustainable Architecture"],
    "M.Pharm": ["Pharmaceutics", "Pharmacology", "Pharmacognosy"],
    "PG Diploma": ["Data Science", "Digital Marketing", "Financial Management", "HR Management"]
};

const streamSpecializationMap = {
    // Diploma Specializations
    "Production": ["Manufacturing", "Quality Control", "Industrial Engineering"],
    "Automobile": ["Automotive Design", "Vehicle Maintenance", "Hybrid Technology"],
    "Mechatronics": ["Robotics", "Automation", "Control Systems"],
    "Tool Engineering": ["Tool Design", "CAD/CAM", "Precision Engineering"],
    "Construction": ["Project Management", "Building Technology", "Estimation"],
    "Environmental": ["Water Resources", "Pollution Control", "Waste Management"],
    "Structural": ["Earthquake Engineering", "Bridge Design", "High-Rise Structures"],
    "Transportation": ["Traffic Engineering", "Highway Design", "Public Transport"],
    "Power Systems": ["Renewable Energy", "Smart Grids", "Power Distribution"],
    "Control Systems": ["Process Control", "Automation", "Instrumentation"],
    "Instrumentation": ["Industrial Instrumentation", "Biomedical Instrumentation", "Control Instruments"],
    "Telecommunication": ["Wireless Communication", "Optical Communication", "Network Security"],
    "Embedded Systems": ["IoT", "Real-time Systems", "Embedded Linux"],
    "VLSI": ["ASIC Design", "FPGA", "VLSI Testing"],
    "AI & ML": ["Deep Learning", "Computer Vision", "Natural Language Processing"],
    "Cyber Security": ["Ethical Hacking", "Network Security", "Digital Forensics"],
    "Cloud Computing": ["AWS", "Azure", "Cloud Security"],
    "Data Science": ["Big Data", "Machine Learning", "Data Visualization"],
    "Networking": ["CCNA", "Network Administration", "Wireless Networks"],
    "Database Management": ["SQL", "NoSQL", "Database Administration"],
    "Web Technologies": ["Full Stack Development", "UI/UX", "Web Security"],
    "Automotive Design": ["Vehicle Dynamics", "Aerodynamics", "Chassis Design"],
    "Vehicle Dynamics": ["Suspension Design", "Brake Systems", "Steering Systems"],
    "Alternative Fuels": ["Electric Vehicles", "Hybrid Technology", "Hydrogen Fuel"],
    "Petrochemical": ["Refinery Operations", "Petroleum Processing", "Polymer Technology"],
    "Polymer": ["Plastics Technology", "Rubber Technology", "Polymer Processing"],
    "Process Control": ["Automation", "Process Optimization", "DCS Systems"],
    "Medical Biotechnology": ["Genetic Engineering", "Stem Cell Technology", "Biopharmaceuticals"],
    "Industrial Biotechnology": ["Enzyme Technology", "Fermentation Technology", "Biofuels"],
    "Bioinformatics": ["Genomics", "Proteomics", "Computational Biology"],
    "Urban Design": ["Sustainable Cities", "Urban Planning", "Transportation Planning"],
    "Landscape Architecture": ["Eco-friendly Design", "Public Spaces", "Recreational Planning"],
    "Sustainable Design": ["Green Building", "Energy Efficiency", "Eco Materials"],
    "Pharmaceutics": ["Formulation", "Drug Delivery", "Quality Assurance"],
    "Pharmacology": ["Clinical Research", "Toxicology", "Drug Development"],
    "Pharmacognosy": ["Herbal Medicine", "Natural Products", "Phytochemistry"],
    
    // Degree Specializations
    "Computer Science": ["AI", "ML", "Data Science", "Cyber Security"],
    "Electronics": ["VLSI", "Embedded Systems", "Communication"],
    "Mechanical": ["Thermal", "Design", "Production"],
    "Civil": ["Structural", "Environmental", "Transportation"],
    "Information Science": ["Networking", "DBMS", "Web Tech"],
    "Accounting": ["Auditing", "Taxation", "Financial Reporting"],
    "Finance": ["Investment", "Banking", "Financial Markets"],
    "Taxation": ["Direct Tax", "Indirect Tax", "International Tax"],
    "Banking": ["Retail Banking", "Corporate Banking", "Investment Banking"],
    "E-Commerce": ["Digital Marketing", "Online Business", "E-Payments"],
    "Marketing": ["Digital Marketing", "Brand Management", "Sales"],
    "HR": ["Recruitment", "Training", "Compensation"],
    "International Business": ["Global Marketing", "Export Management", "Cross-cultural Management"],
    "Entrepreneurship": ["Startup Management", "Venture Capital", "Small Business"],
    "Economics": ["Macro Economics", "Micro Economics", "Econometrics"],
    "Psychology": ["Clinical", "Counselling", "Organizational"],
    "Sociology": ["Urban", "Rural", "Criminology"],
    "History": ["Ancient", "Medieval", "Modern"],
    "Political Science": ["International Relations", "Public Administration", "Political Theory"],
    "Sustainable Architecture": ["Green Building", "Energy Efficiency", "Eco Design"],
    "Urban Design": ["City Planning", "Urban Renewal", "Smart Cities"],
    "Pharmaceutics": ["Drug Formulation", "Quality Control", "Biopharmaceutics"],
    "Food Production": ["Culinary Arts", "Bakery", "Garde Manger"],
    "Front Office": ["Reservation", "Reception", "Concierge"],
    "Housekeeping": ["Accommodation", "Laundry", "Public Area"],
    "Food & Beverage": ["Restaurant", "Bar", "Room Service"],
    "Cyber Security": ["Network Security", "Ethical Hacking", "Digital Forensics", "Cyber Law & Compliance", "Information security management", "Cloud Security", "Application & Web Security", "Cryptography", "Penetration Testing", "Incident Response & Threat Analysis"],
    "Data Science": ["Machine Learning", "Artificial Intelligence (AI)", "Big Data Analytics", "Data Visualization", "Statistical Analysis", "Business Analytics", "Predictive Modeling", "Data Mining", "Database Management", "Deep Learning"],
    "AI & Machine Learning": ["Deep Learning", "Natural Language Processing (NLP)", "Computer Vision", "Robotics & Automation", "Neural Networks", "Reinforcement Learning", "Data Analytics for AI", "Cognitive Computing", "AI in Cyber Security", "AI for Cloud & Edge Computing"],
    "Cloud Computing": ["Cloud Architecture", "Cloud Security", "Cloud Application Development", "DevOps & Cloud Automation", "Virtualization Technologies", "Serverless Computing", "Cloud Networking", "Big Data on Cloud", "Cloud Storage Management", "Multi-Cloud & Hybrid Cloud Systems"],
    "Cloud and Mobile Computing": ["Mobile Application Development", "Cloud Application Development", "Hybrid Mobile-Cloud Solutions", "Cross-Platform App Development", "Mobile Security & Cloud Security Integration", "Cloud-Native Mobile Apps", "Backend as a Service (BaaS) for Mobile Apps", "Performance Optimization for Cloud Apps", "IoT Integration with Mobile and Cloud", "Serverless Mobile Architecture"],
    "DevOps": ["Infrastructure as Code (IaC)", "Containerization (Docker, Kubernetes)", "Cloud DevOps (AWS, Azure, Google Cloud)", "Configuration Management (Ansible, Puppet, Chef)", "Monitoring & Logging (Prometheus, ELK Stack)", "Automation Scripting (Python, Bash)", "Security in DevOps (DevSecOps)", "Microservices Architecture", "Release & Deployment Management"],
    
    // Post Graduate Specializations
    "Computer Science": ["AI", "ML", "Data Science"],
    "VLSI": ["ASIC", "FPGA", "VLSI Testing"],
    "Power Systems": ["Renewable Energy", "Smart Grid", "Power Electronics"],
    "Structural Engineering": ["Earthquake", "Bridge", "High-Rise"],
    "Finance": ["Investment Banking", "Financial Risk Management", "Corporate Finance", "Banking & Insurance", "Portfolio Management", "Financial Markets"],
    "Marketing": ["Digital Marketing", "Brand Management", "Sales & Distribution Management", "Market Research", "Advertising & Media Management"],
    "Human Resource Management": ["Talent Acquisition", "Training & Development", "Employee Relations", "Performance Management", "Organizational Behavior", "Compensation & Benefits"],
    "Operations Management": ["Quality Management", "Project Management", "Logistics Management", "Production Planning & Control", "Supply Chain Optimization"],
    "Information Technology / Systems": ["IT Project Management", "Data Management & Analytics", "Cybersecurity Management", "ERP (Enterprise Resource Planning)", "Business Process Management"],
    "International Business": ["Export-Import Management", "Global Marketing", "International Finance", "Cross-Cultural Management", "International Trade Laws"],
    "Supply Chain Management": ["Procurement & Inventory Management", "Transportation Management", "Warehouse Management", "Logistics & Distribution", "Strategic Sourcing"],
    "Entrepreneurship & Innovation": ["New Venture Creation", "Startup Management", "Business Strategy & Innovation", "Venture Capital & Funding"],
    "Business Analytics / Data Analytics": ["Predictive Analytics", "Big Data Management", "Data Visualization", "Decision Science"],
    "Physics": ["Nuclear", "Solid State", "Astrophysics"],
    "Chemistry": ["Organic", "Inorganic", "Physical"],
    "Mathematics": ["Pure", "Applied", "Statistics"],
    "Biotechnology": ["Medical", "Industrial", "Plant"],
    "Accounting": ["Financial", "Management", "Auditing"],
    "Finance": ["Corporate", "Investment", "Banking"],
    "Business Studies": ["Strategy", "Entrepreneurship", "International Business"],
    "Cyber Security": ["Network Security", "Ethical Hacking", "Digital Forensics", "Cyber Law & Compliance", "Information security management", "Cloud Security", "Application & Web Security", "Cryptography", "Penetration Testing", "Incident Response & Threat Analysis"],
    "Data Science": ["Machine Learning", "Artificial Intelligence (AI)", "Big Data Analytics", "Data Visualization", "Statistical Analysis", "Business Analytics", "Predictive Modeling", "Data Mining", "Database Management", "Deep Learning"],
    "AI & Machine Learning": ["Deep Learning", "Natural Language Processing (NLP)", "Computer Vision", "Robotics & Automation", "Neural Networks", "Reinforcement Learning", "Data Analytics for AI", "Cognitive Computing", "AI in Cyber Security", "AI for Cloud & Edge Computing"],
    "Cloud Computing": ["Cloud Architecture", "Cloud Security", "Cloud Application Development", "DevOps & Cloud Automation", "Virtualization Technologies", "Serverless Computing", "Cloud Networking", "Big Data on Cloud", "Cloud Storage Management", "Multi-Cloud & Hybrid Cloud Systems"],
    "Cloud and Mobile Computing": ["Mobile Application Development", "Cloud Application Development", "Hybrid Mobile-Cloud Solutions", "Cross-Platform App Development", "Mobile Security & Cloud Security Integration", "Cloud-Native Mobile Apps", "Backend as a Service (BaaS) for Mobile Apps", "Performance Optimization for Cloud Apps", "IoT Integration with Mobile and Cloud", "Serverless Mobile Architecture"],
    "DevOps": ["Infrastructure as Code (IaC)", "Containerization (Docker, Kubernetes)", "Cloud DevOps (AWS, Azure, Google Cloud)", "Configuration Management (Ansible, Puppet, Chef)", "Monitoring & Logging (Prometheus, ELK Stack)", "Automation Scripting (Python, Bash)", "Security in DevOps (DevSecOps)", "Microservices Architecture", "Release & Deployment Management"],
    "English": ["Literature", "Linguistics", "Creative Writing"],
    "Economics": ["Development", "Financial", "International"],
    "History": ["Ancient", "Medieval", "Modern"],
    "Sociology": ["Urban", "Rural", "Gender Studies"],
    "Urban Design": ["Sustainable Cities", "Transportation", "Public Spaces"],
    "Landscape Architecture": ["Eco Design", "Recreational", "Urban Green Spaces"],
    "Sustainable Architecture": ["Green Materials", "Energy Efficient", "Zero Carbon"],
    "Pharmaceutics": ["Drug Delivery", "Formulation", "Nanotechnology"],
    "Data Science": ["ML", "Big Data", "Data Engineering"],
    "Digital Marketing": ["SEO", "Content Marketing", "Social Media"],
    "Financial Management": ["Corporate Finance", "Investment", "Risk"],
    "HR Management": ["Talent Acquisition", "Performance", "Compensation"]
};

// ===================================================================
// START OF CORRECTION: VALIDATION FUNCTIONS MOVED TO GLOBAL SCOPE
// ===================================================================

// Form validation
function validateStep(step) {
    let valid = true;
    const stepElement = document.getElementById(`step${step}`);
    if (!stepElement) return false;

    // Clear previous errors
    stepElement.querySelectorAll('.form-group').forEach(group => group.classList.remove('error'));
    stepElement.querySelectorAll('.is-invalid').forEach(field => field.classList.remove('is-invalid'));

    switch(step) {
        case 1:
            valid = validateStep1();
            break;
        case 2:
            valid = validateStep2();
            break;
        case 3:
            valid = validateStep3();
            break;
        case 4:
            valid = validateStep4();
            break;
        case 5:
            valid = validateStep5();
            break;
    }

    return valid;
}

// Personal info step validation
function validateStep1() {
    let isValid = true;
    const stepElement = document.getElementById('step1');
    const errors = [];

    // Validate required fields
    stepElement.querySelectorAll('[required]').forEach(input => {
        const formGroup = input.closest('.form-group');
        let inputValid = true;

        if (!input.value.trim()) {
            inputValid = false;
        } else if (input.type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(input.value.trim())) inputValid = false;
        } else if (['mobile', 'alternate_mobile'].includes(input.name)) {
            const mobileRegex = /^[0-9]{10}$/;
            // Only validate alternate_mobile if it's not empty
            if (input.name === 'mobile' && !mobileRegex.test(input.value.trim())) {
                inputValid = false;
            } else if (input.name === 'alternate_mobile' && input.value.trim() && !mobileRegex.test(input.value.trim())) {
                inputValid = false;
            }
        } else if (input.name === 'pincode') {
            const pincodeRegex = /^[0-9]{6}$/;
            if (!pincodeRegex.test(input.value.trim())) inputValid = false;
        }

        if (!inputValid) {
            isValid = false;
            if (formGroup) formGroup.classList.add('error');
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    });

    if (!isValid) {
        showValidationErrors(['Please fill all required fields correctly']);
    }

    return isValid;
}

// Academic step validation
function validateStep2() {
    let isValid = true;
    const errors = [];
    
    // Check SSLC
    const sslcCourse = document.querySelector('select[name="sslc_course"]').value;
    if (sslcCourse === 'SSLC') {
        const sslcYear = document.querySelector('select[name="sslc_year"]').value;
        const sslcMarks = document.querySelector('input[name="sslc_marks"]').value;
        
        if (!sslcYear) {
            errors.push('SSLC year is required');
            highlightField('select[name="sslc_year"]');
            isValid = false;
        }
        if (!sslcMarks) {
            errors.push('SSLC marks are required');
            highlightField('input[name="sslc_marks"]');
            isValid = false;
        }
    }
    
    // Check other qualifications
    const qualifications = [
        { name: 'PUC', course: 'puc_course', pursuing: 'puc_pursuing' },
        { name: 'ITI', course: 'iti_course', pursuing: 'iti_pursuing' },
        { name: 'Diploma', course: 'diploma_course', pursuing: 'diploma_pursuing' },
        { name: 'Degree', course: 'degree_course', pursuing: 'degree_pursuing' },
        { name: 'Post Grad', course: 'pg_course', pursuing: 'pg_pursuing' }
    ];
    
    qualifications.forEach(qual => {
        const courseSelect = document.querySelector(`select[name="${qual.course}"]`);
        if (courseSelect && courseSelect.value && courseSelect.value !== '') {
            const pursuingCheckbox = document.querySelector(`input[name="${qual.pursuing}"]`);
            const isPursuing = pursuingCheckbox && pursuingCheckbox.checked;
            
            // If course is selected (not "Not Completed"), validate fields
            const row = courseSelect.closest('tr');
            const yearSelect = row.querySelector('select.year-select');
            const marksInput = row.querySelector('input.marks-input');
            
            // Year is always required if course is selected
            if (yearSelect && !yearSelect.value) {
                errors.push(`${qual.name} year is required`);
                highlightField(yearSelect);
                isValid = false;
            }
            
            // Marks are only required if not pursuing
            if (!isPursuing && marksInput && !marksInput.value) {
                errors.push(`${qual.name} marks are required (unless currently pursuing)`);
                highlightField(marksInput);
                isValid = false;
            }
        }
    });
    
    // Show errors if any
    if (!isValid) {
        showValidationErrors(errors);
    } else {
        // Clear any previous error highlights
        clearAcademicErrorHighlights();
    }
    
    return isValid;
}

// Skills step validation
function validateStep3() {
    // let isValid = true;
    // const skillsCheckboxes = document.querySelectorAll('input[name="skills[]"]:checked');
    // if (skillsCheckboxes.length === 0) {
    //     showValidationErrors(['Please select at least one technical skill']);
    //     isValid = false;
    // }
    // return isValid;
    return true;
}

// ===================================================================
// START OF SCRIPT.JS CORRECTION
// ===================================================================

// Preferences step validation
function validateStep4() {
    let isValid = true;
    const stepElement = document.getElementById('step4');
    const errors = [];
    
    // Clear previous errors
    stepElement.querySelectorAll('.form-group.error').forEach(group => group.classList.remove('error'));
    stepElement.querySelectorAll('.is-invalid').forEach(field => field.classList.remove('is-invalid'));

    // 1. Validate all generic required fields (which are dynamically set by inline JS)
    stepElement.querySelectorAll('[required]').forEach(input => {
        const formGroup = input.closest('.form-group');
        let inputValid = true;

        if (input.type === 'file') {
             if (!input.files || input.files.length === 0) {
                 inputValid = false;
             }
        } else if (!input.value.trim()) {
            inputValid = false;
        }

        if (!inputValid) {
            isValid = false;
            if (formGroup) formGroup.classList.add('error');
            input.classList.add('is-invalid');
            
            let labelText = input.name;
            const label = formGroup ? formGroup.querySelector('label') : null;
            if (label) labelText = label.textContent.replace('*', '').trim();
            
            errors.push(`Please provide: ${labelText}`);
        } else {
            if(formGroup) formGroup.classList.remove('error');
            input.classList.remove('is-invalid');
        }
    });

    // 2. Custom validation for Resume (it's a special case: file OR checkbox)
    const noResumeCheckbox = document.getElementById('no_resume');
    const resumeInput = document.getElementById('resume');
    const resumeGroup = resumeInput.closest('.form-group');

    // If "I don't have a resume" is not checked AND no file is selected
    if (!noResumeCheckbox.checked && (!resumeInput.files || resumeInput.files.length === 0)) {
        errors.push('Please upload your resume or check "I don\'t have a resume"');
        if (resumeGroup) resumeGroup.classList.add('error');
        isValid = false;
    } 
    // If a file IS selected, validate it (even if not required, good to validate)
    else if (resumeInput.files && resumeInput.files.length > 0) {
        const file = resumeInput.files[0];
        const allowedTypes = ['application/pdf', 'application/msword', 
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'image/jpeg', 'image/png'];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!allowedTypes.includes(file.type)) {
            errors.push('Resume: Please upload PDF, DOC, DOCX, JPG, or PNG files only');
            if (resumeGroup) resumeGroup.classList.add('error');
            isValid = false;
        }
        
        if (file.size > maxSize) {
            errors.push('Resume: File size must be less than 5MB');
            if (resumeGroup) resumeGroup.classList.add('error');
            isValid = false;
        }
    }
    
    // 3. Custom validation for disability file uploads (if files are present)
    const disabilityFiles = [
        { input: document.getElementById('udid_card'), label: 'UDID Card' },
        { input: document.getElementById('disability_certificate'), label: 'Disability Certificate' }
    ];
    
    disabilityFiles.forEach(item => {
        if (item.input && item.input.files.length > 0) {
            const file = item.input.files[0];
            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png']; // Stricter for certs
            const maxSize = 5 * 1024 * 1024; // 5MB
            const fileGroup = item.input.closest('.form-group');

            if (!allowedTypes.includes(file.type)) {
                errors.push(`${item.label}: Please upload PDF, JPG, or PNG files only`);
                if(fileGroup) fileGroup.classList.add('error');
                isValid = false;
            }
            if (file.size > maxSize) {
                errors.push(`${item.label}: File size must be less than 5MB`);
                if(fileGroup) fileGroup.classList.add('error');
                isValid = false;
            }
        }
    });

    if (!isValid) {
        // Use a Set to remove duplicate messages
        const uniqueErrors = [...new Set(errors)];
        showValidationErrors(uniqueErrors);
    }
    
    return isValid;
}

// ===================================================================
// END OF SCRIPT.JS CORRECTION
// ===================================================================

// Review step validation
function validateStep5() {
    const terms = document.getElementById('terms');
    if (terms && !terms.checked) {
        showValidationErrors(['You must agree to the Terms and Conditions']);
        return false;
    }
    return true;
}

// Utility functions for validation
function highlightField(selector) {
    const field = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (field) {
        field.style.borderColor = '#ff3860';
        field.style.boxShadow = '0 0 0 2px rgba(255, 56, 96, 0.2)';
        field.classList.add('is-invalid');
    }
}

function clearAcademicErrorHighlights() {
    const academicFields = document.querySelectorAll('#step2 select, #step2 input');
    academicFields.forEach(field => {
        field.style.borderColor = '';
        field.style.boxShadow = '';
        field.classList.remove('is-invalid');
    });
}

function showValidationErrors(errors) {
    alert('Please fix the following errors:\n• ' + errors.join('\n• '));
}

// ===================================================================
// END OF CORRECTION
// ===================================================================

// Function to handle pursuing checkbox
function handlePursuingCheckbox(checkbox) {
    const row = checkbox.closest('tr');
    const markingSystemSelect = row.querySelector('.marking-system');
    const marksInput = row.querySelector('.marks-input');
    const yearSelect = row.querySelector('.year-select');
    
    if (checkbox.checked) {
        // If pursuing, disable and clear marks and marking system
        markingSystemSelect.disabled = true;
        marksInput.disabled = true;
        marksInput.value = '';
        markingSystemSelect.selectedIndex = 0;
        
        // Year should still be required and enabled
        if (yearSelect) {
            yearSelect.disabled = false;
            yearSelect.required = true;
        }
    } else {
        // If not pursuing, enable marks and marking system
        markingSystemSelect.disabled = false;
        marksInput.disabled = false;
        
        if (yearSelect) {
            yearSelect.required = true;
        }
    }
}

// Function to toggle qualification fields (UPDATED to include pursuing checkboxes)
function toggleQualificationFields(qualificationSelect) {
    const row = qualificationSelect.closest('tr');
    const fields = row.querySelectorAll('select:not(.course-select), input[type="text"], input[type="number"]');
    const pursuingCheckbox = row.querySelector('.pursuing-checkbox');
    
    if (qualificationSelect.value !== '' && qualificationSelect.value !== 'Not Completed') {
        // Enable all fields in the row
        fields.forEach(field => {
            field.disabled = false;
            if (field.tagName === 'SELECT' && field.classList.contains('year-select')) {
                populateYearDropdown(field);
                field.required = true;
            }
        });
        
        // Enable pursuing checkbox and reset its state
        if (pursuingCheckbox) {
            pursuingCheckbox.disabled = false;
            // Reset pursuing state when course changes
            pursuingCheckbox.checked = false;
            handlePursuingCheckbox(pursuingCheckbox); // Apply initial state
        }
    } else {
        // Disable all fields in the row and clear values
        fields.forEach(field => {
            field.disabled = true;
            if (field.tagName === 'SELECT') {
                field.selectedIndex = 0;
                field.required = false;
            } else {
                field.value = '';
            }
        });
        
        // Disable and uncheck pursuing checkbox
        if (pursuingCheckbox) {
            pursuingCheckbox.disabled = true;
            pursuingCheckbox.checked = false;
            handlePursuingCheckbox(pursuingCheckbox); // Apply disabled state
        }
    }
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Populate SSLC year (required)
    const sslcYear = document.querySelector('select[name="sslc_year"]');
    populateYearDropdown(sslcYear, true);
    
    // Add event listeners to course selection dropdowns
    const courseSelects = document.querySelectorAll('.course-select');
    courseSelects.forEach(select => {
        // Initial state
        toggleQualificationFields(select);
        
        // Change event
        select.addEventListener('change', function() {
            toggleQualificationFields(this);
        });
    });
    
    // Add event listeners to pursuing checkboxes
    document.querySelectorAll('.pursuing-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            handlePursuingCheckbox(this);
        });
        
        // Initialize current state
        handlePursuingCheckbox(checkbox);
    });
    
    // PUC event listener (no pursuing checkbox)
    const pucCourse = document.querySelector('select[name="puc_course"]');
    pucCourse.addEventListener('change', function() {
        const pucRow = this.closest('tr');
        const pucFields = pucRow.querySelectorAll('select:not([name="puc_course"]), input');
        
        if (this.value !== '') {
            pucFields.forEach(field => {
                field.disabled = false;
                if (field.name === 'puc_year') {
                    populateYearDropdown(field);
                    field.required = true;
                }
            });
        } else {
            pucFields.forEach(field => {
                field.disabled = true;
                if (field.tagName === 'SELECT') {
                    field.selectedIndex = 0;
                    field.required = false;
                } else {
                    field.value = '';
                }
            });
        }
    });
    
    // ITI event listener
    const itiCourse = document.querySelector('select[name="iti_course"]');
    itiCourse.addEventListener('change', function() {
        const itiRow = this.closest('tr');
        const itiFields = itiRow.querySelectorAll('select:not([name="iti_course"]), input, .pursuing-checkbox');
        
        if (this.value !== '') {
            itiFields.forEach(field => {
                field.disabled = false;
                if (field.name === 'iti_year') {
                    populateYearDropdown(field);
                    field.required = true;
                }
            });
        } else {
            itiFields.forEach(field => {
                field.disabled = true;
                if (field.tagName === 'SELECT') {
                    field.selectedIndex = 0;
                    field.required = false;
                } else if (field.type === 'checkbox') {
                    field.checked = false;
                } else {
                    field.value = '';
                }
            });
        }
        
        // Handle pursuing checkbox state for ITI
        const itiPursuingCheckbox = itiRow.querySelector('.pursuing-checkbox');
        if (itiPursuingCheckbox) {
            handlePursuingCheckbox(itiPursuingCheckbox);
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Initialize date restrictions
    const today = new Date();
    const dobInput = document.querySelector('input[name="dob"]');
    if (dobInput) {
        dobInput.max = today.toISOString().split('T')[0];
    }

    // Initialize course dropdowns
    initCourseDropdowns();
    
    // Step management
    let currentStep = 1;
    const totalSteps = 5;

    updateProgressBar();
    updateStepIndicators();

    // Next step buttons
    document.querySelectorAll('.next-step').forEach(button => {
        button.addEventListener('click', () => {
            if (validateStep(currentStep)) { // This will now work
                const currentElement = document.getElementById(`step${currentStep}`);
                if (currentElement) currentElement.classList.remove('active');

                currentStep++;
                const nextElement = document.getElementById(`step${currentStep}`);
                if (currentStep <= totalSteps && nextElement) {
                    nextElement.classList.add('active');
                }

                updateProgressBar();
                updateStepIndicators();

                if (currentStep === totalSteps) {
                    generateReviewData();
                }
            }
        });
    });

    // Previous step buttons
    document.querySelectorAll('.prev-step').forEach(button => {
        button.addEventListener('click', () => {
            const currentElement = document.getElementById(`step${currentStep}`);
            if (currentElement) currentElement.classList.remove('active');

            currentStep--;
            const prevElement = document.getElementById(`step${currentStep}`);
            if (prevElement) prevElement.classList.add('active');

            updateProgressBar();
            updateStepIndicators();
        });
    });

    // Form submission
    const registrationForm = document.getElementById('registrationForm');
    if (registrationForm) {
        registrationForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // Validate all steps before submission
            let allValid = true;
            for (let step = 1; step <= 5; step++) {
                if (!validateStep(step)) { // This will now work
                    allValid = false;
                    // Go to the first invalid step
                    const currentElement = document.getElementById(`step${currentStep}`);
                    if (currentElement) currentElement.classList.remove('active');
                    
                    currentStep = step;
                    const nextElement = document.getElementById(`step${currentStep}`);
                    if (nextElement) nextElement.classList.add('active');
                    
                    updateProgressBar();
                    updateStepIndicators();
                    break;
                }
            }

            if (!allValid) {
                alert('Please fix all validation errors before submitting.');
                return;
            }

            // Get form data
            const formData = new FormData(this);

            // Get skills, languages, and industries
            const skills = Array.from(document.querySelectorAll('input[name="skills[]"]:checked')).map(el => el.value);
            const languages = Array.from(document.querySelectorAll('input[name="languages[]"]:checked')).map(el => el.value);
            const industries = Array.from(document.querySelectorAll('input[name="industries[]"]:checked')).map(el => el.value);
            const otherSkills = document.querySelector('input[name="other_skills"]').value;
            const otherLanguages = document.querySelector('input[name="other_languages"]').value;
            const otherIndustries = document.querySelector('input[name="other_industries"]').value;

            // Combine skills with other skills if provided
            let allSkills = skills;
            if (otherSkills) {
                allSkills = [...skills, ...otherSkills.split(',').map(s => s.trim()).filter(s => s)];
            }

            let allLanguages = languages;
            if (otherLanguages) {
                allLanguages = [...languages, ...otherLanguages.split(',').map(s => s.trim()).filter(s => s)];
            }

            let allIndustries = industries;
            if (otherIndustries) {
                allIndustries = [...industries, ...otherIndustries.split(',').map(s => s.trim()).filter(s => s)];
            }

            // Override the skills, languages, and industries in the formData
            formData.set('skills', allSkills.join(','));
            formData.set('languages', allLanguages.join(','));
            formData.set('industries', allIndustries.join(','));

            // Handle pursuing fields - only set values for qualifications that are actually selected
            console.log("=== DEBUG: Starting pursuing fields processing ===");

            const qualificationMap = {
                'iti_pursuing': 'iti_course',
                'diploma_pursuing': 'diploma_course', 
                'degree_pursuing': 'degree_course',
                'pg_pursuing': 'pg_course'
            };

            Object.entries(qualificationMap).forEach(([pursuingField, courseField]) => {
                console.log(`Processing: ${pursuingField} -> ${courseField}`);
                
                const pursuingCheckbox = document.querySelector(`input[name="${pursuingField}"]`);
                const courseSelect = document.querySelector(`select[name="${courseField}"]`);
                
                console.log(`Checkbox found:`, pursuingCheckbox);
                console.log(`Course select found:`, courseSelect);
                console.log(`Course select value:`, courseSelect?.value);
                console.log(`Checkbox checked:`, pursuingCheckbox?.checked);
                
                if (pursuingCheckbox && courseSelect) {
                    // Only set pursuing value if the course is actually selected (not empty or "Not Completed")
                    if (courseSelect.value && courseSelect.value !== '' && courseSelect.value !== 'Not Completed') {
                        const value = pursuingCheckbox.checked ? '1' : '0';
                        console.log(`Setting ${pursuingField} to ${value} (course is selected)`);
                        formData.set(pursuingField, value);
                    } else {
                        // Course not selected, so set pursuing to 0
                        console.log(`Setting ${pursuingField} to 0 (course not selected)`);
                        formData.set(pursuingField, '0');
                    }
                } else {
                    // Field doesn't exist, set to 0
                    console.log(`Setting ${pursuingField} to 0 (elements not found)`);
                    formData.set(pursuingField, '0');
                }
                console.log('---');
            });

            console.log("=== DEBUG: Finished pursuing fields processing ===");

            // Debug: log form data
            console.log('FormData contents:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }

            fetch('employee_registration.php', {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const text = await response.text();
                console.log('Server response:', text);
                
                try {
                    // Try parsing as JSON
                    return JSON.parse(text);
                } catch (e) {
                    console.error("Server did not return JSON", text);
                    // If parsing fails, check for common PHP error indicators
                    if (text.includes('<b>Fatal error</b>') || text.includes('<b>Parse error</b>') || text.includes('<b>Warning</b>') || text.includes('Column count doesn')) {
                        throw new Error("Server returned a PHP error page. Check the console log for the server response text.");
                    }
                    // Otherwise, throw a generic error
                    throw new Error("Invalid JSON response from server. Check console.");
                }
            })
            .then(data => {
                if (data.status === 'success') {
                    console.log('Registration successful:', data);
                    const currentElement = document.getElementById(`step${currentStep}`);
                    if (currentElement) currentElement.classList.remove('active');

                    const successMsg = document.getElementById('successMessage');
                    if (successMsg) {
                        successMsg.classList.add('active');
                        successMsg.style.display = 'block';
                    }
                } else {
                    console.error('Server returned error:', data);
                    alert('Error: ' + (data.message || 'Something went wrong. Please check the console for details.'));
                }
            })
            .catch(error => {
                console.error('Form submission error:', error);
                alert('Error submitting form: ' + error.message + '. Please check the console for details and try again.');
            });
        });
    }

    // Initialize course dropdown functionality
    function initCourseDropdowns() {
        // Add event listeners to all course dropdowns
        document.querySelectorAll('.course-select').forEach(courseSelect => {
            courseSelect.addEventListener('change', function() {
                const courseValue = this.value;
                const row = this.closest('tr');
                const streamSelect = row.querySelector('.stream-select');
                const specializationSelect = row.querySelector('.specialization-select');
                
                // Reset stream and specialization
                streamSelect.innerHTML = '<option value="" selected disabled>Select Stream</option>';
                specializationSelect.innerHTML = '<option value="" selected disabled>Select Specialization</option>';
                
                // Populate stream dropdown
                if (courseStreamMap[courseValue]) {
                    courseStreamMap[courseValue].forEach(stream => {
                        const option = document.createElement('option');
                        option.value = stream;
                        option.textContent = stream;
                        streamSelect.appendChild(option);
                    });
                }
            });
        });
        
        // Add event listeners to all stream dropdowns
        document.querySelectorAll('.stream-select').forEach(streamSelect => {
            streamSelect.addEventListener('change', function() {
                const streamValue = this.value;
                const row = this.closest('tr');
                const specializationSelect = row.querySelector('.specialization-select');
                
                // Reset specialization
                specializationSelect.innerHTML = '<option value="" selected disabled>Select Specialization</option>';
                
                // Populate specialization dropdown
                if (streamSpecializationMap[streamValue]) {
                    streamSpecializationMap[streamValue].forEach(specialization => {
                        const option = document.createElement('option');
                        option.value = specialization;
                        option.textContent = specialization;
                        specializationSelect.appendChild(option);
                    });
                }
            });
        });
    }

    // Progress bar update
    function updateProgressBar() {
        const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
        const bar = document.querySelector('.progress-bar');
        if (bar) bar.style.width = `${progress}%`;
    }

    // Step indicators update
    function updateStepIndicators() {
        document.querySelectorAll('.progress-steps > [data-step]').forEach(step => {
            const stepNum = parseInt(step.getAttribute('data-step'));
            step.classList.remove('completed', 'active');

            if (stepNum < currentStep) {
                step.classList.add('completed');
            } else if (stepNum === currentStep) {
                step.classList.add('active');
            }
        });
    }

    // Generate review data
    function generateReviewData() {
        const form = document.getElementById('registrationForm');
        if (!form) return;

        let reviewHTML = '';

        // Personal Information
        const dobValue = form.dob?.value;
        const formattedDob = dobValue ? dobValue.split('-').reverse().join('-') : '-';
        reviewHTML += `
            <div class="review-section">
                <h4>Personal Information</h4>
                <div class="review-item"><div class="review-label">College Name:</div><div class="review-value">${form.college_name?.value || '-'}</div></div>
                <div class="review-item"><div class="review-label">University:</div><div class="review-value">${form.university?.value || '-'}</div></div>
                <div class="review-item"><div class="review-label">College Location:</div><div class="review-value">${form.college_location?.value || '-'}</div></div>
                <div class="review-item"><div class="review-label">Full Name:</div><div class="review-value">${form.full_name?.value || '-'}</div></div>
                <div class="review-item"><div class="review-label">Mobile:</div><div class="review-value">${form.country_code?.value || ''} ${form.mobile?.value || '-'}</div></div>
                <div class="review-item"><div class="review-label">Email:</div><div class="review-value">${form.email?.value || '-'}</div></div>
                <div class="review-item"><div class="review-label">DOB:</div><div class="review-value">${formattedDob}</div></div>
                <div class="review-item"><div class="review-label">Permanent Address:</div><div class="review-value">${form.permanent_address?.value || '-'}</div></div>
            </div>`;

        // Academic Details
        reviewHTML += `
            <div class="review-section">
                <h4>Academic Details</h4>`;

        // Function to check if pursuing
        function isPursuing(qualificationName) {
            const checkbox = document.querySelector(`input[name="${qualificationName}_pursuing"]`);
            return checkbox && checkbox.checked;
        }

        // SSLC
        if (form.sslc_course?.value === 'Below SSLC') {
            reviewHTML += `<div class="review-item"><div class="review-label">SSLC:</div><div class="review-value">Below SSLC (Not Completed)</div></div>`;
        } else if (form.sslc_year?.value) {
            const sslcMarkingSystem = form.sslc_marking_system?.value || 'Percentage';
            const sslcUnit = sslcMarkingSystem === 'CGPA' ? 'CGPA' : '%';
            if (form.sslc_marks?.value) {
                reviewHTML += `<div class="review-item"><div class="review-label">SSLC:</div><div class="review-value">${form.sslc_marks.value} ${sslcUnit} (${form.sslc_year.value})</div></div>`;
            } else {
                reviewHTML += `<div class="review-item"><div class="review-label">SSLC:</div><div class="review-value">Pursuing (${form.sslc_year.value})</div></div>`;
            }
        }

        // PUC
        if (form.puc_course?.value && form.puc_year?.value) {
            const isPursuingPUC = isPursuing('puc'); // Note: PUC doesn't have a pursuing checkbox in your HTML, but this is how you would check
            const pucMarkingSystem = form.puc_marking_system?.value || 'Percentage';
            const pucUnit = pucMarkingSystem === 'CGPA' ? 'CGPA' : '%';
            
            let pucText = '';
            if (isPursuingPUC) {
                pucText = `Pursuing (${form.puc_year.value})`;
            } else if (form.puc_marks?.value) {
                pucText = `${form.puc_marks.value} ${pucUnit} (${form.puc_year.value})`;
            }
            
            if (pucText) {
                if (form.puc_stream?.value && form.puc_stream.value !== 'Science') {
                    pucText += ` - ${form.puc_stream.value}`;
                }
                if (form.puc_specialization?.value) {
                    pucText += ` - ${form.puc_specialization.value}`;
                }
                reviewHTML += `<div class="review-item"><div class="review-label">PUC:</div><div class="review-value">${pucText}</div></div>`;
            }
        }

        // ITI
        if (form.iti_course?.value && form.iti_year?.value) {
            const isPursuingITI = isPursuing('iti');
            const itiMarkingSystem = form.iti_marking_system?.value || 'Percentage';
            const itiUnit = itiMarkingSystem === 'CGPA' ? 'CGPA' : '%';
            
            let itiText = `${form.iti_course.value}`;
            if (isPursuingITI) {
                itiText += ` - Pursuing (${form.iti_year.value})`;
            } else if (form.iti_marks?.value) {
                itiText += ` - ${form.iti_marks.value} ${itiUnit} (${form.iti_year.value})`;
            } else {
                itiText += ` - ${form.iti_year.value}`;
            }
            
            if (form.iti_stream?.value) itiText += ` - ${form.iti_stream.value}`;
            if (form.iti_specialization?.value) itiText += ` - ${form.iti_specialization.value}`;
            reviewHTML += `<div class="review-item"><div class="review-label">ITI:</div><div class="review-value">${itiText}</div></div>`;
        }

        // Diploma
        if (form.diploma_course?.value && form.diploma_year?.value) {
            const isPursuingDiploma = isPursuing('diploma');
            const diplomaMarkingSystem = form.diploma_marking_system?.value || 'Percentage';
            const diplomaUnit = diplomaMarkingSystem === 'CGPA' ? 'CGPA' : '%';
            
            let diplomaText = `${form.diploma_course.value}`;
            if (isPursuingDiploma) {
                diplomaText += ` - Pursuing (${form.diploma_year.value})`;
            } else if (form.diploma_marks?.value) {
                diplomaText += ` - ${form.diploma_marks.value} ${diplomaUnit} (${form.diploma_year.value})`;
            } else {
                diplomaText += ` - ${form.diploma_year.value}`;
            }
            
            if (form.diploma_stream?.value) diplomaText += ` - ${form.diploma_stream.value}`;
            if (form.diploma_specialization?.value) diplomaText += ` - ${form.diploma_specialization.value}`;
            reviewHTML += `<div class="review-item"><div class="review-label">Diploma:</div><div class="review-value">${diplomaText}</div></div>`;
        }

        // Degree
        if (form.degree_course?.value && form.degree_year?.value) {
            const isPursuingDegree = isPursuing('degree');
            const degreeMarkingSystem = form.degree_marking_system?.value || 'Percentage';
            const degreeUnit = degreeMarkingSystem === 'CGPA' ? 'CGPA' : '%';
            
            // Get actual course value (handle "Others")
            let degreeCourseValue = form.degree_course.value;
            if (degreeCourseValue === 'Others' && form.degree_course_other?.value) {
                degreeCourseValue = form.degree_course_other.value;
            }
            
            // Get actual stream value (handle "Others")
            let degreeStreamValue = form.degree_stream?.value || '';
            if (form.degree_stream_other?.value) {
                degreeStreamValue = form.degree_stream_other.value;
            }
            
            // Get actual specialization value (handle "Others")
            let degreeSpecializationValue = form.degree_specialization?.value || '';
            if (form.degree_specialization_other?.value) {
                degreeSpecializationValue = form.degree_specialization_other.value;
            }
            
            let degreeText = `${degreeCourseValue}`;
            if (isPursuingDegree) {
                degreeText += ` - Pursuing (${form.degree_year.value})`;
            } else if (form.degree_marks?.value) {
                degreeText += ` - ${form.degree_marks.value} ${degreeUnit} (${form.degree_year.value})`;
            } else {
                degreeText += ` - ${form.degree_year.value}`;
            }
            
            if (degreeStreamValue) degreeText += ` - ${degreeStreamValue}`;
            if (degreeSpecializationValue) degreeText += ` - ${degreeSpecializationValue}`;
            reviewHTML += `<div class="review-item"><div class="review-label">Degree:</div><div class="review-value">${degreeText}</div></div>`;
        }

        // Post Grad
        if (form.pg_course?.value && form.pg_year?.value) {
            const isPursuingPG = isPursuing('pg');
            const pgMarkingSystem = form.pg_marking_system?.value || 'Percentage';
            const pgUnit = pgMarkingSystem === 'CGPA' ? 'CGPA' : '%';
            
            // Get actual course value (handle "Others")
            let pgCourseValue = form.pg_course.value;
            if (pgCourseValue === 'Others' && form.pg_course_other?.value) {
                pgCourseValue = form.pg_course_other.value;
            }
            
            // Get actual stream value (handle "Others")
            let pgStreamValue = form.pg_stream?.value || '';
            if (form.pg_stream_other?.value) {
                pgStreamValue = form.pg_stream_other.value;
            }
            
            // Get actual specialization value (handle "Others")
            let pgSpecializationValue = form.pg_specialization?.value || '';
            if (form.pg_specialization_other?.value) {
                pgSpecializationValue = form.pg_specialization_other.value;
            }
            
            let pgText = `${pgCourseValue}`;
            if (isPursuingPG) {
                pgText += ` - Pursuing (${form.pg_year.value})`;
            } else if (form.pg_marks?.value) {
                pgText += ` - ${form.pg_marks.value} ${pgUnit} (${form.pg_year.value})`;
            } else {
                pgText += ` - ${form.pg_year.value}`;
            }
            
            if (pgStreamValue) pgText += ` - ${pgStreamValue}`;
            if (pgSpecializationValue) pgText += ` - ${pgSpecializationValue}`;
            reviewHTML += `<div class="review-item"><div class="review-label">Post Graduation:</div><div class="review-value">${pgText}</div></div>`;
        }

        // Doctorate and Experience
        reviewHTML += `
            <div class="review-item"><div class="review-label">Doctorate:</div><div class="review-value">${form.doctorate?.value || 'No'}</div></div>
            <div class="review-item"><div class="review-label">Work Experience:</div><div class="review-value">${form.experience?.value || 'No'}</div></div>
        </div>`;

        // Skills & Aspirations
        const skills = Array.from(document.querySelectorAll('input[name="skills[]"]:checked')).map(el => el.value);
        const languages = Array.from(document.querySelectorAll('input[name="languages[]"]:checked')).map(el => el.value);
        const industries = Array.from(document.querySelectorAll('input[name="industries[]"]:checked')).map(el => el.value);

        const otherSkills = form.other_skills?.value.trim() || '';
        const otherLanguages = form.other_languages?.value.trim() || '';
        const otherIndustries = form.other_industries?.value.trim() || '';

        let allSkills = [...skills];
        if (otherSkills) allSkills.push(otherSkills);
        
        let allLanguages = [...languages];
        if (otherLanguages) allLanguages.push(otherLanguages);
        
        let allIndustries = [...industries];
        if (otherIndustries) allIndustries.push(otherIndustries);

        reviewHTML += `
            <div class="review-section">
                <h4>Skills & Aspirations</h4>
                <div class="review-item"><div class="review-label">Technical Skills:</div><div class="review-value">${allSkills.join(', ') || 'None'}</div></div>
                <div class="review-item"><div class="review-label">Languages:</div><div class="review-value">${allLanguages.join(', ') || 'None'}</div></div>
                <div class="review-item"><div class="review-label">Industry Aspirations:</div><div class="review-value">${allIndustries.join(', ') || 'None'}</div></div>
                <div class="review-item"><div class="review-label">Relocation:</div><div class="review-value">${form.relocation?.value || 'No'}</div></div>
            </div>`;

        // Preferences
        reviewHTML += `
            <div class="review-section">
                <h4>Preferences</h4>
                <div class="review-item"><div class="review-label">Higher Studies:</div><div class="review-value">${form.higher_studies?.value || 'No'}</div></div>
                <div class="review-item"><div class="review-label">Shift Work:</div><div class="review-value">${form.shift_work?.value || 'No'}</div></div>
                <div class="review-item"><div class="review-label">Passport:</div><div class="review-value">${form.passport?.value || 'No'}</div></div>
                <div class="review-item"><div class="review-label">Driving License:</div><div class="review-value">${form.driving_license?.value || 'No'}</div></div>
            </div>`;
            
        // ===================================================================
        // START OF DISABILITY REVIEW CORRECTION (WITH VIEW LINKS)
        // ===================================================================
        const disabilityValue = form.disability?.value || 'No';
        reviewHTML += `
            <div class="review-section">
                <h4>Disability Details</h4>
                <div class="review-item"><div class="review-label">Disability:</div><div class="review-value">${disabilityValue}</div></div>`;

        if (disabilityValue === 'Yes') {
            const disabilityType = form.disability_type?.value || 'Not specified';
            const disabilityPercentage = form.disability_percentage?.value ? `${form.disability_percentage.value}%` : 'Not specified';
            const hasUdid = form.has_udid?.value || 'No';
            
            reviewHTML += `<div class="review-item"><div class="review-label">Disability Type:</div><div class="review-value">${disabilityType}</div></div>`;
            reviewHTML += `<div class="review-item"><div class="review-label">Disability Percentage:</div><div class="review-value">${disabilityPercentage}</div></div>`;
            reviewHTML += `<div class="review-item"><div class="review-label">Has UDID Card:</div><div class="review-value">${hasUdid}</div></div>`;
            
            if (hasUdid === 'Yes') {
                const udidNumber = form.udid_number?.value || 'Not specified';
                const udidFile = form.udid_card?.files[0];
                let udidFileInfo = 'Not provided';
                
                if (udidFile) {
                    const fileSizeMB = (udidFile.size / 1024 / 1024).toFixed(2);
                    const fileUrl = URL.createObjectURL(udidFile); // Create URL
                    udidFileInfo = `
                        Uploaded: ${udidFile.name} (${fileSizeMB} MB)
                        <a href="${fileUrl}" target="_blank" class="btn-view-resume" title="View File in new tab">View</a>
                    `; // Add link
                }
                
                reviewHTML += `<div class="review-item"><div class="review-label">UDID Number:</div><div class="review-value">${udidNumber}</div></div>`;
                reviewHTML += `<div class="review-item"><div class="review-label">UDID Card File:</div><div class="review-value">${udidFileInfo}</div></div>`;
            } else { // hasUdid === 'No'
                const certFile = form.disability_certificate?.files[0];
                let certFileInfo = 'Not provided';
                
                if (certFile) {
                    const fileSizeMB = (certFile.size / 1024 / 1024).toFixed(2);
                    const fileUrl = URL.createObjectURL(certFile); // Create URL
                    certFileInfo = `
                        Uploaded: ${certFile.name} (${fileSizeMB} MB)
                        <a href="${fileUrl}" target="_blank" class="btn-view-resume" title="View File in new tab">View</a>
                    `; // Add link
                }
                reviewHTML += `<div class="review-item"><div class="review-label">Disability Certificate:</div><div class="review-value">${certFileInfo}</div></div>`;
            }
        }
        
        reviewHTML += `</div>`; // Close review-section
        // ===================================================================
        // END OF DISABILITY REVIEW CORRECTION
        // ===================================================================
            
        // Resume Information
        const resumeFile = document.querySelector('input[name="resume"]').files[0];
        const noResumeChecked = document.querySelector('input[name="no_resume"]').checked;

        let resumeInfo = 'No resume uploaded';
        if (noResumeChecked) {
            resumeInfo = 'No resume (user selected "I don\'t have a resume")';
        } else if (resumeFile) {
            // Calculate file size in MB
            const fileSizeMB = (resumeFile.size / 1024 / 1024).toFixed(2);
            
            // Create a temporary URL for the file to make it viewable
            const resumeUrl = URL.createObjectURL(resumeFile);
            
            // Build the HTML string with the file info and a "View" button
            resumeInfo = `
                Uploaded: ${resumeFile.name} (${fileSizeMB} MB)
                <a href="${resumeUrl}" target="_blank" class="btn-view-resume" title="View Resume in new tab">View</a>
            `;
        }

        reviewHTML += `
            <div class="review-section">
                <h4>Resume Information</h4>
                <div class="review-item"><div class="review-label">Resume:</div><div class="review-value">${resumeInfo}</div></div>
            </div>`;

        // Update review container
        const reviewContainer = document.getElementById('reviewData');
        if (reviewContainer) reviewContainer.innerHTML = reviewHTML;
    }
});