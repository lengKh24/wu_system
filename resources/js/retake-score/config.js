import { baseUri } from "../app";

/**
 * Static configuration for Score's retake-score page.
 * No DOM access, no state — safe to import anywhere.
 */
export const CONFIG = {
    REGISTRATIONS_API: baseUri('retake-registrations'),
    TERMS_API: baseUri('retake-terms'),
    EXAM_TYPES_API: baseUri('exam-types'),
    DEBOUNCE_DELAY: 300,
    LOCALE: 'en-GB',
    PER_PAGE: 15,
};
