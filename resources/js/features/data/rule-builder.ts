import { onAlpineInit } from '../../runtime/alpine';

export type Combinator = 'and' | 'or';

export type RuleNode = {
    id: string;
    kind: 'rule';
    field: string;
    operator: string;
    values: unknown[];
    locked?: boolean;
};

export type GroupNode = {
    id: string;
    kind: 'group';
    combinator: Combinator;
    children: Array<RuleNode | GroupNode>;
    locked?: boolean;
};

export type ActionNode = {
    id: string;
    type: string;
    params: Record<string, unknown>;
};

export type RuleBuilderValue = {
    when: GroupNode;
    then: {
        ifTrue: ActionNode[];
        ifFalse: ActionNode[];
    };
};

export type FieldOption = {
    value: string | number | boolean;
    label: string;
    color?: string | null;
    icon?: string | null;
};

export type FieldConfig = {
    key: string;
    label: string;
    type: 'select' | 'multiselect' | 'text' | 'number' | 'date';
    icon?: string | null;
    placeholder?: string | null;
    options: FieldOption[];
    operators: string[];
    defaultOperator: string;
};

export type ActionParamDef = {
    key: string;
    label: string;
    type: 'select' | 'text';
    options?: FieldOption[];
    placeholder?: string | null;
};

export type ActionDef = {
    key: string;
    label: string;
    params?: ActionParamDef[];
};

export type RuleBuilderConfig = {
    value: RuleBuilderValue | null;
    fields: FieldConfig[];
    actions: ActionDef[];
    maxDepth: number;
    disabled: boolean;
    labels: Record<string, string>;
    operatorLabels: Record<string, string>;
};

type DragKind = 'when' | 'then-true' | 'then-false';

type DragState = {
    kind: DragKind | null;
    parentId: string | null;
    nodeId: string | null;
    overId: string | null;
};

function uid(prefix = 'rb'): string {
    return prefix + Math.random().toString(36).slice(2, 10);
}

function emptyValue(): RuleBuilderValue {
    return {
        when: {
            id: uid('g'),
            kind: 'group',
            combinator: 'and',
            children: [],
        },
        then: {
            ifTrue: [],
            ifFalse: [],
        },
    };
}

function singleOptionValue(config: FieldConfig | undefined | null): FieldOption['value'] | null {
    if (!config) return null;
    if (config.type !== 'select' && config.type !== 'multiselect') return null;
    if (config.options.length !== 1) return null;
    return config.options[0].value;
}

function normalizeRule(raw: Partial<RuleNode> | null | undefined, fields: Record<string, FieldConfig>): RuleNode {
    const field = raw?.field ?? Object.keys(fields)[0] ?? '';
    const config = fields[field];
    const operator = raw?.operator ?? config?.defaultOperator ?? 'is';

    let values = Array.isArray(raw?.values) ? [...raw!.values!] : [];
    if (values.length === 0 && operator !== 'empty' && operator !== 'not_empty') {
        const single = singleOptionValue(config);
        if (single !== null) {
            values = [single];
        }
    }

    return {
        id: raw?.id ?? uid('r'),
        kind: 'rule',
        field,
        operator,
        values,
        locked: Boolean(raw?.locked),
    };
}

function normalizeGroup(
    raw: Partial<GroupNode> | null | undefined,
    fields: Record<string, FieldConfig>,
    depth = 0,
    maxDepth = 3,
): GroupNode {
    const children = Array.isArray(raw?.children)
        ? raw!.children!
              .map((child) => {
                  if (child && (child as GroupNode).kind === 'group') {
                      if (depth + 1 >= maxDepth) {
                          return null;
                      }

                      return normalizeGroup(child as GroupNode, fields, depth + 1, maxDepth);
                  }

                  return normalizeRule(child as RuleNode, fields);
              })
              .filter(Boolean) as Array<RuleNode | GroupNode>
        : [];

    return {
        id: raw?.id ?? uid('g'),
        kind: 'group',
        combinator: raw?.combinator === 'or' ? 'or' : 'and',
        children,
        locked: Boolean(raw?.locked),
    };
}

function normalizeAction(raw: Partial<ActionNode> | null | undefined, actions: ActionDef[]): ActionNode {
    const type = raw?.type ?? actions[0]?.key ?? '';
    const def = actions.find((a) => a.key === type);
    const params: Record<string, unknown> = { ...(raw?.params ?? {}) };

    for (const param of def?.params ?? []) {
        if (!(param.key in params)) {
            params[param.key] = param.type === 'select' ? (param.options?.[0]?.value ?? '') : '';
        }
    }

    return {
        id: raw?.id ?? uid('a'),
        type,
        params,
    };
}

function normalizeValue(
    raw: Partial<RuleBuilderValue> | null | undefined,
    fields: Record<string, FieldConfig>,
    actions: ActionDef[],
    maxDepth: number,
): RuleBuilderValue {
    if (!raw || typeof raw !== 'object') {
        return emptyValue();
    }

    return {
        when: normalizeGroup(raw.when ?? emptyValue().when, fields, 0, maxDepth),
        then: {
            ifTrue: Array.isArray(raw.then?.ifTrue)
                ? raw.then!.ifTrue!.map((a) => normalizeAction(a, actions))
                : [],
            ifFalse: Array.isArray(raw.then?.ifFalse)
                ? raw.then!.ifFalse!.map((a) => normalizeAction(a, actions))
                : [],
        },
    };
}

function cloneValue(value: RuleBuilderValue): RuleBuilderValue {
    return JSON.parse(JSON.stringify(value)) as RuleBuilderValue;
}

if (typeof window !== 'undefined') {
    const NK_RB_BOOT = ((window as any).__NK_RULE_BUILDER_BOOT__ ??= { booted: false });

    if (!NK_RB_BOOT.booted) {
        NK_RB_BOOT.booted = true;

        onAlpineInit(() => {
            (window as any).Alpine.data('neuraRuleBuilder', (config: RuleBuilderConfig) => {
                const fieldList = Array.isArray(config.fields) ? config.fields : [];
                const fieldConfigs = Object.fromEntries(fieldList.map((f) => [f.key, f])) as Record<string, FieldConfig>;
                const actions = Array.isArray(config.actions) ? config.actions : [];
                const maxDepth = Number(config.maxDepth) > 0 ? Number(config.maxDepth) : 3;

                return {
                    value: normalizeValue(config.value, fieldConfigs, actions, maxDepth),
                    fieldConfigs,
                    fieldsList: fieldList.map((f) => ({ key: f.key, label: f.label, icon: f.icon ?? null })),
                    actions,
                    actionMap: Object.fromEntries(actions.map((a) => [a.key, a])) as Record<string, ActionDef>,
                    maxDepth,
                    isDisabled: Boolean(config.disabled),
                    labels: config.labels ?? {},
                    operatorLabels: config.operatorLabels ?? {},
                    open: null as string | null,
                    chipDraft: {} as Record<string, string>,
                    drag: {
                        kind: null,
                        parentId: null,
                        nodeId: null,
                        overId: null,
                    } as DragState,
                    syncing: false,

                    init() {
                        (this as any).$watch(
                            'value',
                            () => {
                                if (!this.syncing) this.emit();
                            },
                            { deep: true },
                        );
                    },

                    emit() {
                        const snapshot = cloneValue(this.value);
                        (this as any).$dispatch('rule-builder-changed', { value: snapshot });
                        (this as any).$root?._x_model?.set(snapshot);

                        const root = (this as any).$root as HTMLElement | undefined;
                        const wireModel = root?.getAttributeNames().find((n) => n.startsWith('wire:model'));
                        if ((this as any).$wire && wireModel && root) {
                            (this as any).$wire.set(
                                root.getAttribute(wireModel),
                                snapshot,
                                wireModel.includes('.live'),
                            );
                        }
                    },

                    syncFromModel() {
                        const model = (this as any).$root?._x_model;
                        if (!model) return;

                        let next: unknown;
                        try {
                            next = model.get();
                        } catch {
                            return;
                        }
                        if (next === undefined || next === null) return;
                        if (JSON.stringify(next) === JSON.stringify(this.value)) return;

                        this.syncing = true;
                        this.value = normalizeValue(
                            next as RuleBuilderValue,
                            this.fieldConfigs,
                            this.actions,
                            this.maxDepth,
                        );
                        queueMicrotask(() => {
                            this.syncing = false;
                        });
                    },

                    label(key: string, replacements: Record<string, string | number> = {}): string {
                        let text = this.labels[key] ?? key;
                        for (const [k, v] of Object.entries(replacements)) {
                            text = text.replace(`{${k}}`, String(v));
                        }
                        return text;
                    },

                    operatorLabel(op: string): string {
                        return this.operatorLabels[op] ?? op;
                    },

                    fieldConfig(key: string): FieldConfig | null {
                        return this.fieldConfigs[key] ?? null;
                    },

                    actionDef(type: string): ActionDef | null {
                        return this.actionMap[type] ?? null;
                    },

                    countConditions(group: GroupNode): number {
                        let n = 0;
                        for (const child of group.children) {
                            if (child.kind === 'rule') n += 1;
                            else n += this.countConditions(child);
                        }
                        return n;
                    },

                    findGroup(id: string, root: GroupNode = this.value.when): GroupNode | null {
                        if (root.id === id) return root;
                        for (const child of root.children) {
                            if (child.kind === 'group') {
                                const found = this.findGroup(id, child);
                                if (found) return found;
                            }
                        }
                        return null;
                    },

                    findParent(nodeId: string, root: GroupNode = this.value.when): GroupNode | null {
                        for (const child of root.children) {
                            if (child.id === nodeId) return root;
                            if (child.kind === 'group') {
                                const found = this.findParent(nodeId, child);
                                if (found) return found;
                            }
                        }
                        return null;
                    },

                    depthOf(groupId: string, root: GroupNode = this.value.when, depth = 0): number {
                        if (root.id === groupId) return depth;
                        for (const child of root.children) {
                            if (child.kind === 'group') {
                                const d = this.depthOf(groupId, child, depth + 1);
                                if (d >= 0) return d;
                            }
                        }
                        return -1;
                    },

                    canAddGroup(parentId: string): boolean {
                        const depth = this.depthOf(parentId);
                        return depth >= 0 && depth + 1 < this.maxDepth;
                    },

                    defaultRule(): RuleNode {
                        const first = this.fieldsList[0];
                        return normalizeRule({ field: first?.key ?? '' }, this.fieldConfigs);
                    },

                    defaultGroup(): GroupNode {
                        return {
                            id: uid('g'),
                            kind: 'group',
                            combinator: 'or',
                            children: [this.defaultRule()],
                        };
                    },

                    defaultAction(): ActionNode {
                        return normalizeAction({}, this.actions);
                    },

                    addRule(parentId: string) {
                        if (this.isDisabled) return;
                        const parent = this.findGroup(parentId);
                        if (!parent || parent.locked) return;
                        parent.children.push(this.defaultRule());
                    },

                    addGroup(parentId: string) {
                        if (this.isDisabled) return;
                        if (!this.canAddGroup(parentId)) return;
                        const parent = this.findGroup(parentId);
                        if (!parent || parent.locked) return;
                        parent.children.push(this.defaultGroup());
                    },

                    addCondition(parentId: string) {
                        this.addRule(parentId);
                    },

                    removeNode(nodeId: string) {
                        if (this.isDisabled) return;
                        const parent = this.findParent(nodeId);
                        if (!parent || parent.locked) return;
                        const node = parent.children.find((c) => c.id === nodeId);
                        if (!node || node.locked) return;
                        parent.children = parent.children.filter((c) => c.id !== nodeId);
                    },

                    toggleCombinator(groupId: string) {
                        if (this.isDisabled) return;
                        const group = this.findGroup(groupId);
                        if (!group || group.locked) return;
                        group.combinator = group.combinator === 'and' ? 'or' : 'and';
                    },

                    setField(ruleId: string, field: string) {
                        if (this.isDisabled) return;
                        const parent = this.findParent(ruleId);
                        const rule = parent?.children.find((c) => c.id === ruleId);
                        if (!rule || rule.kind !== 'rule' || rule.locked) return;
                        const config = this.fieldConfig(field);
                        rule.field = field;
                        rule.operator = config?.defaultOperator ?? 'is';

                        const single = singleOptionValue(config);
                        rule.values = single !== null ? [single] : [];
                    },

                    setOperator(ruleId: string, operator: string) {
                        if (this.isDisabled) return;
                        const parent = this.findParent(ruleId);
                        const rule = parent?.children.find((c) => c.id === ruleId);
                        if (!rule || rule.kind !== 'rule' || rule.locked) return;
                        rule.operator = operator;
                        if (operator === 'empty' || operator === 'not_empty') {
                            rule.values = [];
                        } else if (rule.values.length === 0) {
                            const single = singleOptionValue(this.fieldConfig(rule.field));
                            if (single !== null) {
                                rule.values = [single];
                            }
                        }
                    },

                    setRuleValues(ruleId: string, values: unknown[]) {
                        if (this.isDisabled) return;
                        const parent = this.findParent(ruleId);
                        const rule = parent?.children.find((c) => c.id === ruleId);
                        if (!rule || rule.kind !== 'rule' || rule.locked) return;
                        rule.values = values;
                    },

                    setTextValue(ruleId: string, value: string) {
                        this.setRuleValues(ruleId, value === '' ? [] : [value]);
                    },

                    setSelectValue(ruleId: string, value: unknown) {
                        this.setRuleValues(ruleId, value === '' || value === null || value === undefined ? [] : [value]);
                    },

                    toggleMultiValue(ruleId: string, value: unknown) {
                        if (this.isDisabled) return;
                        const parent = this.findParent(ruleId);
                        const rule = parent?.children.find((c) => c.id === ruleId);
                        if (!rule || rule.kind !== 'rule' || rule.locked) return;
                        rule.values = rule.values.includes(value)
                            ? rule.values.filter((v) => v !== value)
                            : [...rule.values, value];
                    },

                    removeMultiValue(ruleId: string, value: unknown) {
                        if (this.isDisabled) return;
                        const parent = this.findParent(ruleId);
                        const rule = parent?.children.find((c) => c.id === ruleId);
                        if (!rule || rule.kind !== 'rule' || rule.locked) return;
                        rule.values = rule.values.filter((v) => v !== value);
                    },

                    optionLabel(fieldKey: string, value: unknown): string {
                        const opt = this.fieldConfig(fieldKey)?.options.find((o) => o.value === value);
                        return opt?.label ?? String(value ?? '');
                    },

                    needsValue(operator: string): boolean {
                        return operator !== 'empty' && operator !== 'not_empty';
                    },

                    addAction(branch: 'ifTrue' | 'ifFalse') {
                        if (this.isDisabled) return;
                        this.value.then[branch].push(this.defaultAction());
                    },

                    removeAction(branch: 'ifTrue' | 'ifFalse', actionId: string) {
                        if (this.isDisabled) return;
                        this.value.then[branch] = this.value.then[branch].filter((a) => a.id !== actionId);
                    },

                    setActionType(branch: 'ifTrue' | 'ifFalse', actionId: string, type: string) {
                        if (this.isDisabled) return;
                        const action = this.value.then[branch].find((a) => a.id === actionId);
                        if (!action) return;
                        const next = normalizeAction({ id: action.id, type, params: {} }, this.actions);
                        action.type = next.type;
                        action.params = next.params;
                    },

                    setActionParam(branch: 'ifTrue' | 'ifFalse', actionId: string, key: string, value: unknown) {
                        if (this.isDisabled) return;
                        const action = this.value.then[branch].find((a) => a.id === actionId);
                        if (!action) return;
                        action.params = { ...action.params, [key]: value };
                    },

                    // --- Drag & drop (same-parent reorder) ---
                    onDragStart(kind: DragKind, parentId: string, nodeId: string, event: DragEvent) {
                        if (this.isDisabled) return;
                        this.drag = { kind, parentId, nodeId, overId: null };
                        if (event.dataTransfer) {
                            event.dataTransfer.effectAllowed = 'move';
                            event.dataTransfer.setData('text/plain', nodeId);
                        }
                    },

                    onDragEnd() {
                        this.drag = { kind: null, parentId: null, nodeId: null, overId: null };
                    },

                    onDragOver(kind: DragKind, parentId: string, overId: string, event: DragEvent) {
                        if (!this.drag.nodeId || this.drag.kind !== kind || this.drag.parentId !== parentId) return;
                        if (this.drag.nodeId === overId) return;
                        event.preventDefault();
                        this.drag.overId = overId;
                    },

                    onDrop(kind: DragKind, parentId: string, overId: string, event: DragEvent) {
                        event.preventDefault();
                        if (!this.drag.nodeId || this.drag.kind !== kind || this.drag.parentId !== parentId) {
                            this.onDragEnd();
                            return;
                        }
                        if (this.drag.nodeId === overId) {
                            this.onDragEnd();
                            return;
                        }

                        if (kind === 'when') {
                            const parent = this.findGroup(parentId);
                            if (!parent || parent.locked) {
                                this.onDragEnd();
                                return;
                            }
                            const from = parent.children.findIndex((c) => c.id === this.drag.nodeId);
                            const to = parent.children.findIndex((c) => c.id === overId);
                            if (from < 0 || to < 0) {
                                this.onDragEnd();
                                return;
                            }
                            const moving = parent.children[from];
                            if (moving.locked) {
                                this.onDragEnd();
                                return;
                            }
                            parent.children.splice(from, 1);
                            parent.children.splice(to, 0, moving);
                        } else {
                            const branch = kind === 'then-true' ? 'ifTrue' : 'ifFalse';
                            const list = this.value.then[branch];
                            const from = list.findIndex((a) => a.id === this.drag.nodeId);
                            const to = list.findIndex((a) => a.id === overId);
                            if (from < 0 || to < 0) {
                                this.onDragEnd();
                                return;
                            }
                            const moving = list[from];
                            list.splice(from, 1);
                            list.splice(to, 0, moving);
                        }

                        this.onDragEnd();
                    },

                    isDragging(nodeId: string): boolean {
                        return this.drag.nodeId === nodeId;
                    },

                    isDragOver(nodeId: string): boolean {
                        return this.drag.overId === nodeId;
                    },

                    toggleOpen(key: string) {
                        if (this.isDisabled) return;
                        this.open = this.open === key ? null : key;
                    },

                    closeOpen() {
                        this.open = null;
                    },
                };
            });
        });
    }
}
