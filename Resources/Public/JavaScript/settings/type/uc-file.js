import { html } from 'lit';
import { customElement, property } from 'lit/decorators.js';
import { live } from 'lit/directives/live.js';
import { BaseElement } from '@typo3/backend/settings/type/base.js';

export const componentName = 'typo3-backend-settings-type-ucfile';

const defaultEntry = {
    dataProcessingService: '',
    file: '',
    value: '',
};

export class UcfileTypeElement extends BaseElement {

    static properties = {
        value: { type: Array },
    };

    updateValue(value, index, property) {
        const copy = [...this.value];
        copy[index] = { ...this.value[index], [property]: value };
        this.value = copy;
    }
    addValue(index, value = null) {
        this.value = this.value.toSpliced(index + 1, 0, value ?? defaultEntry);
    }
    removeValue(index) {
        this.value = this.value.toSpliced(index, 1);
    }

    renderItem(value, index) {
        return html `
            <tr>
                ${Object.getOwnPropertyNames(defaultEntry).map(propertyName => html`
                    <td width="33%">
                        <input
                            id=${`${this.formid}${index > 0 ? '-' + index : ''}`}
                            type="text"
                            class="form-control"
                            ?readonly=${this.readonly}
                            .value=${live(value[propertyName] ?? '')}
                            @change=${(e) => this.updateValue(e.target.value, index, propertyName)}
                        />
                    </td>
                `)}
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-default" type="button" ?disabled=${this.readonly} @click=${() => this.addValue(index)}>
                            <typo3-backend-icon identifier="actions-plus" size="small"></typo3-backend-icon>
                        </button>
                        <button class="btn btn-default" type="button" ?disabled=${this.readonly} @click=${() => this.removeValue(index)}>
                            <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }
    render() {
        if ((this.value || []).length === 0) {
            this.value = [ defaultEntry ];
        }

        return html `
            <div class="form-control-wrap">
                <div class="table-fit">
                    <table class="table table-hover">
                        <tbody>
                            ${this.value.map((v, i) => this.renderItem(v, i))}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }
};

window.customElements.define(componentName, UcfileTypeElement);
