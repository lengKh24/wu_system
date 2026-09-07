import { baseUri } from "../app";

/**
 * Static configuration for REG's retake exam Main List.
 * No DOM access, no state — safe to import anywhere.
 */
export const CONFIG = {
    REGISTRATIONS_API: baseUri('retake-registrations'),
    REGISTRATIONS_EXPORT_API: baseUri('retake-registrations-export'),
    BATCHES_API: baseUri('retake-batches'),
    TERMS_API: baseUri('retake-terms'),
    EXAM_TYPES_API: baseUri('exam-types'),
    CAMPUSES_API: baseUri('campuses'),
    DEBOUNCE_DELAY: 300,
    LOCALE: 'en-GB',
    PER_PAGE: 15,
};
