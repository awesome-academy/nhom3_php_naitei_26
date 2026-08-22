const CATEGORY_NAMES_EN = {
    ADMINISTRATION: 'Administration',
    EDUCATION: 'Education',
    HEALTHCARE: 'Healthcare',
    CONSTRUCTION: 'Construction',
    NATURAL_RESOURCES: 'Natural Resources & Environment',
};

const CATEGORY_NAME_LOOKUP_EN = {
    'Hành chính': 'Administration',
    'Giáo dục': 'Education',
    'Y tế': 'Healthcare',
    'Xây dựng': 'Construction',
    'Tài nguyên và Môi trường': 'Natural Resources & Environment',
};

const SERVICE_CONTENT_EN = {
    CIVIL_STATUS_CERTIFICATE: {
        name: 'Civil Status Certificate',
        description: 'Issue a certificate of civil status information based on registered records.',
        requirements: 'A valid citizen identity document is required.',
        documents: { citizen_id_copy: 'Citizen ID copy' },
    },
    CONSTRUCTION_PERMIT: {
        name: 'Construction Permit',
        description: 'Receive and assess applications for construction permits.',
        requirements: 'The project must comply with planning and current construction regulations.',
        fields: { construction_area: 'Construction area' },
        documents: { design_drawing: 'Design drawing' },
    },
    PUBLIC_SCHOOL_ENROLLMENT: {
        name: 'Public School Enrollment',
        description: 'Register a student for enrollment at a public school online.',
        requirements: 'The student must meet the applicable eligibility and enrollment-area requirements.',
        documents: {
            birth_certificate: 'Birth certificate copy',
            residence_confirmation: 'Residence confirmation',
        },
    },
    HEALTHCARE_SUPPORT_REGISTRATION: {
        name: 'Healthcare Support Registration',
        description: 'Apply for healthcare support available to eligible applicants.',
        requirements: 'The applicant must belong to an eligible support group under current regulations.',
        documents: {
            citizen_id_copy: 'Citizen ID copy',
            eligibility_document: 'Eligibility evidence',
        },
    },
    LAND_RECORD_INFORMATION: {
        name: 'Land Record Information Request',
        description: 'Request archived information about a land lot and its land-use rights.',
        requirements: 'The land lot and purpose of the information request must be clearly identified.',
        fields: { land_lot_number: 'Land lot number' },
        documents: { citizen_id_copy: 'Citizen ID copy' },
    },
};

function localizeItems(items, labels = {}) {
    if (!Array.isArray(items)) {
        return items;
    }

    return items.map((item) => {
        if (!item || typeof item === 'string') {
            return item;
        }

        return {
            ...item,
            label: labels[item.code ?? item.name] ?? item.label,
        };
    });
}

export function localizeCategory(category, language) {
    if (language !== 'en' || !category) {
        return category;
    }

    return {
        ...category,
        name: CATEGORY_NAMES_EN[category.code] ?? CATEGORY_NAME_LOOKUP_EN[category.name] ?? category.name,
    };
}

export function localizeService(service, language) {
    if (language !== 'en' || !service) {
        return service;
    }

    const localized = SERVICE_CONTENT_EN[service.code];

    if (!localized) {
        return {
            ...service,
            category_name: CATEGORY_NAME_LOOKUP_EN[service.category_name] ?? service.category_name,
        };
    }

    return {
        ...service,
        name: localized.name,
        description: localized.description,
        requirements: localized.requirements,
        category_name: CATEGORY_NAME_LOOKUP_EN[service.category_name] ?? service.category_name,
        form_schema: localizeItems(service.form_schema, localized.fields),
        document_requirements: localizeItems(service.document_requirements, localized.documents),
    };
}
