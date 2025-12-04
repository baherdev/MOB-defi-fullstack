// src/__tests__/components/RouteCalculator.test.ts
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import RouteCalculator from '../../components/RouteCalculator.vue'
import type { RouteResponse } from '../../types'

// Mock du service API avec named exports
vi.mock('../../services/api', () => ({
    calculateRoute: vi.fn(),
    getDistanceStats: vi.fn(),
    loginApi: vi.fn(),
    getStoredToken: vi.fn(),
    isAuthenticated: vi.fn(),
}))

// Import après le mock
import { calculateRoute } from '../../services/api'

const vuetify = createVuetify({
    components,
    directives,
})

describe('RouteCalculator', () => {
    let wrapper: VueWrapper<any>

    beforeEach(() => {
        vi.clearAllMocks()
        wrapper = mount(RouteCalculator, {
            global: {
                plugins: [vuetify],
            },
        })
    })

    it('should render the form', () => {
        expect(wrapper.find('form').exists()).toBe(true)
        expect(wrapper.text()).toContain('Calculer un trajet')
    })

    it('should have three select fields', () => {
        const selects = wrapper.findAll('.v-select')
        expect(selects.length).toBeGreaterThanOrEqual(3)
    })

    it('should have a submit button', () => {
        const button = wrapper.find('button[type="submit"]')
        expect(button.exists()).toBe(true)
        expect(button.text()).toContain('Calculer')
    })

    it('should call API when form is submitted', async () => {
        // Arrange
        const mockResponse: RouteResponse = {
            id: '1',
            fromStationId: 'MX',
            toStationId: 'GST',
            analyticCode: 'PASSAGER',
            distanceKm: 41.27,
            path: ['MX', 'CGE', 'GST'],
            createdAt: '2025-11-28T10:00:00Z',
        }

        vi.mocked(calculateRoute).mockResolvedValue(mockResponse)

        // Définir les données du formulaire
        wrapper.vm.form = {
            fromStationId: 'MX',
            toStationId: 'GST',
            analyticCode: 'PASSAGER',
        }
        wrapper.vm.formValid = true

        // Act
        await wrapper.vm.handleSubmit()

        // Assert
        expect(calculateRoute).toHaveBeenCalledWith({
            fromStationId: 'MX',
            toStationId: 'GST',
            analyticCode: 'PASSAGER',
        })

        // Attendre que le résultat soit affiché
        await wrapper.vm.$nextTick()

        expect(wrapper.vm.result).toEqual(mockResponse)
        expect(wrapper.vm.loading).toBe(false)
    })

    it('should display error message on API failure', async () => {
        // Arrange
        const errorMessage = 'Station not found'
        vi.mocked(calculateRoute).mockRejectedValue({
            response: {
                data: {
                    message: errorMessage,
                    details: [],
                }
            }
        })

        wrapper.vm.form = {
            fromStationId: 'INVALID',
            toStationId: 'GST',
            analyticCode: 'PASSAGER',
        }
        wrapper.vm.formValid = true

        // Act
        await wrapper.vm.handleSubmit()
        await wrapper.vm.$nextTick()

        // Assert
        expect(wrapper.vm.error).toBeTruthy()
        expect(wrapper.vm.error.message).toBe(errorMessage)
        expect(wrapper.vm.result).toBeNull()
    })

    it('should reset form when reset is called', async () => {
        // Arrange
        wrapper.vm.result = {
            id: '1',
            fromStationId: 'MX',
            toStationId: 'GST',
            analyticCode: 'PASSAGER',
            distanceKm: 41.27,
            path: ['MX', 'GST'],
            createdAt: '2025-11-28T10:00:00Z',
        }
        wrapper.vm.error = { message: 'Some error' }

        // Act
        wrapper.vm.reset()
        await wrapper.vm.$nextTick()

        // Assert
        expect(wrapper.vm.result).toBeNull()
        expect(wrapper.vm.error).toBeNull()
        expect([null, '', undefined]).toContain(wrapper.vm.form.fromStationId)
        expect([null, '', undefined]).toContain(wrapper.vm.form.toStationId)
    })

    it('should format date correctly', () => {
        const dateString = '2025-11-28T10:30:00Z'
        const formatted = wrapper.vm.formatDate(dateString)

        expect(formatted).toBeTruthy()
        expect(typeof formatted).toBe('string')
    })
})
