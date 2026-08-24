<div class="rm-content rm-settings-page">
    <div class="rm-settings-hero">
        <div>
            <span>Administracion de accesos</span>
            <h1>Usuarios y roles</h1>
            <p>Crea usuarios internos, asignales sucursales o comercios, y define que puede hacer cada rol en cada ventana.</p>
        </div>
        <div class="rm-settings-summary">
            <strong>{{ $users->count() }}</strong>
            <span>Usuarios en {{ $company->name }}</span>
        </div>
    </div>

    <div class="rm-tab-switcher rm-access-tabs" role="tablist" aria-label="Usuarios y roles">
        @if ($canViewUsers)
            <button class="{{ $accessTab === 'users' ? 'is-active' : '' }}" type="button" wire:click="setAccessTab('users')">
                Usuarios
                <span>{{ $users->count() }}</span>
            </button>
        @endif
        @if ($canViewRoles)
            <button class="{{ $accessTab === 'roles' ? 'is-active' : '' }}" type="button" wire:click="setAccessTab('roles')">
                Roles
                <span>{{ $roles->count() }}</span>
            </button>
        @endif
    </div>

    <div class="rm-access-tab-panel">
        @if ($canViewUsers && $accessTab === 'users')
        <section class="rm-panel">
            <div class="rm-panel-title">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <h2>Usuarios del sistema</h2>
                </div>
                @if ($canCreateUsers)
                    <button class="rm-button rm-button-primary" type="button" wire:click="createUser">Nuevo usuario</button>
                @endif
            </div>

            @error('userDelete') <div class="rm-inline-error">{{ $message }}</div> @enderror

            <div class="rm-user-filters">
                <label class="rm-search-field">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input wire:model.live.debounce.300ms="userSearch" type="search" placeholder="Buscar usuario por nombre o correo">
                </label>
                <label class="rm-field">
                    <span>Sucursal</span>
                    <select wire:model.live="userBranchFilter">
                        <option value="">Todas</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="rm-field">
                    <span>Rol</span>
                    <select wire:model.live="userRoleFilter">
                        <option value="">Todos</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="rm-field">
                    <span>Estado</span>
                    <select wire:model.live="userStatusFilter">
                        <option value="">Todos</option>
                        <option value="active">Activos</option>
                        <option value="inactive">Inactivos</option>
                    </select>
                </label>
            </div>

            <div class="rm-commerce-list">
                @foreach ($users as $user)
                    @php
                        $userBranches = $user->branches->filter(fn ($branch) => $branches->contains('id', $branch->id));
                        $firstRole = $roles->firstWhere('id', $userBranches->first()?->pivot?->role_id);
                    @endphp
                    <article class="rm-user-row">
                        <span class="rm-user-avatar">
                            @if ($user->profile_photo_path)
                                <img src="{{ asset('storage/'.$user->profile_photo_path) }}" alt="{{ $user->name }}">
                            @else
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            @endif
                        </span>
                        <div class="rm-row-main">
                            <strong>{{ $user->name }}</strong>
                            <span>{{ $user->email }}</span>
                            <div class="rm-commerce-meta">
                                <span>{{ ($user->status ?? 'active') === 'active' ? 'Activo' : 'Inactivo' }}</span>
                                <span>{{ $firstRole?->name ?? 'Sin rol' }}</span>
                                <span>{{ $userBranches->count() }} sucursal(es)</span>
                            </div>
                        </div>
                        <div class="rm-commerce-actions">
                            @if ($canEditUsers)
                                <button type="button" wire:click="editUser({{ $user->id }})">Editar</button>
                            @endif
                            @if ($canDeleteUsers && $user->id !== auth()->id())
                                <button type="button" wire:click="confirmDeleteUser({{ $user->id }})">Deshabilitar</button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
        @endif

        @if ($canViewRoles && $accessTab === 'roles')
        <section class="rm-panel" id="roles">
            <div class="rm-panel-title">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-5"/></svg>
                    <h2>Roles y permisos</h2>
                </div>
                @if ($canCreateRoles)
                    <button class="rm-button rm-button-primary" type="button" wire:click="createRole">Nuevo rol</button>
                @endif
            </div>

            @error('roleDelete') <div class="rm-inline-error">{{ $message }}</div> @enderror

            <div class="rm-role-list-panel">
                @foreach ($roles as $role)
                    <article class="rm-role-mini">
                        <div>
                            <strong>{{ $role->name }}</strong>
                            <span>{{ $role->description }}</span>
                            <div class="rm-commerce-meta">
                                <span>{{ $role->is_system ? 'Rol base' : 'Personalizado' }}</span>
                            </div>
                        </div>
                        <div class="rm-role-actions">
                            @if ($canEditRoles)
                                <button type="button" wire:click="editRole({{ $role->id }})">Editar</button>
                            @endif
                            @if ($canDeleteRoles && ! $role->is_system)
                                <button type="button" wire:click="confirmDeleteRole({{ $role->id }})">Eliminar</button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
        @endif
    </div>

    @if ($showUserModal)
        <div class="rm-modal-backdrop" wire:click="closeUserModal"></div>
        <section class="rm-modal-panel rm-modal-panel-wide" role="dialog" aria-modal="true" aria-labelledby="user-modal-title">
            <div class="rm-modal-title">
                <div>
                    <span>{{ $editingUserId ? 'Editar acceso' : 'Nuevo acceso' }}</span>
                    <h2 id="user-modal-title">{{ $editingUserId ? 'Editar usuario' : 'Crear usuario' }}</h2>
                </div>
                <button type="button" wire:click="closeUserModal" aria-label="Cerrar modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="saveUser" class="rm-form-stack">
                <div class="rm-upload-preview-row">
                    <div class="rm-media-preview">
                        @if ($userPhoto)
                            <img src="{{ $userPhoto->temporaryUrl() }}" alt="Foto temporal">
                        @elseif ($currentUserPhotoPath)
                            <img src="{{ asset('storage/'.$currentUserPhotoPath) }}" alt="Foto actual">
                        @else
                            {{ strtoupper(substr($userName ?: 'U', 0, 1)) }}
                        @endif
                    </div>
                    <label class="rm-field">
                        <span>Imagen de usuario</span>
                        <input wire:model="userPhoto" type="file" accept="image/*">
                        @error('userPhoto') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <label class="rm-field">
                    <span>Nombre</span>
                    <input wire:model="userName" type="text" placeholder="Ana Recepcion">
                    @error('userName') <small>{{ $message }}</small> @enderror
                </label>

                <label class="rm-field">
                    <span>Correo</span>
                    <input wire:model="userEmail" type="email" placeholder="usuario@empresa.com">
                    @error('userEmail') <small>{{ $message }}</small> @enderror
                </label>

                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>{{ $editingUserId ? 'Restablecer contrasena' : 'Contrasena inicial' }}</span>
                        <input wire:model="userPassword" type="password" placeholder="{{ $editingUserId ? 'Nueva contrasena o dejar vacio' : 'Minimo 8 caracteres' }}">
                        @error('userPassword') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="rm-field">
                        <span>Rol</span>
                        <select wire:model="userRoleId">
                            <option value="">Seleccionar rol</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('userRoleId') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <label class="rm-field">
                    <span>Estado</span>
                    <select wire:model="userStatus">
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                    </select>
                    @error('userStatus') <small>{{ $message }}</small> @enderror
                </label>

                <label class="rm-check-option">
                    <input wire:model="userRequiresFaceVerification" type="checkbox">
                    <span>Verificacion facial al ingresar</span>
                    <small>Si cambia de IP o inicia una nueva sesion, Rumika pedira validar el rostro con camara. La primera vez registrara la huella facial del usuario.</small>
                </label>

                <div class="rm-field">
                    <span>Sucursales o comercios</span>
                    <div class="rm-check-grid">
                        @foreach ($branches as $branch)
                            <label class="rm-check-option">
                                <input wire:model="userBranchIds" type="checkbox" value="{{ $branch->id }}">
                                <span>{{ $branch->name }}</span>
                                <small>{{ $branch->businessType?->name ?? 'Sin tipo' }}</small>
                            </label>
                        @endforeach
                    </div>
                    @error('userBranchIds') <small>{{ $message }}</small> @enderror
                    @error('userBranchIds.*') <small>{{ $message }}</small> @enderror
                </div>

                <div class="rm-field">
                    <span>Numeros de WhatsApp permitidos</span>
                    @if ($whatsappChannels->isNotEmpty())
                        <div class="rm-check-grid">
                            @foreach ($whatsappChannels as $channel)
                                <label class="rm-check-option">
                                    <input wire:model="userWhatsappChannelIds" type="checkbox" value="{{ $channel->id }}">
                                    <span>{{ $channel->name }}</span>
                                    <small>{{ $channel->phone_number ?: 'Sin numero visible' }} · {{ $channel->branch?->name ?: 'Todas las sucursales' }}</small>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="rm-dashboard-empty">Todavia no hay numeros configurados en Centro de mensajes.</div>
                    @endif
                    @error('userWhatsappChannelIds.*') <small>{{ $message }}</small> @enderror
                </div>

                <div class="rm-form-actions">
                    <button class="rm-button rm-button-primary" type="submit">{{ $editingUserId ? 'Guardar usuario' : 'Crear usuario' }}</button>
                    <button class="rm-button rm-button-outline" type="button" wire:click="closeUserModal">Cancelar</button>
                </div>
            </form>
        </section>
    @endif

    @if ($showRoleModal)
        <div class="rm-modal-backdrop" wire:click="closeRoleModal"></div>
        <section class="rm-modal-panel rm-modal-panel-xl" role="dialog" aria-modal="true" aria-labelledby="role-modal-title">
            <div class="rm-modal-title">
                <div>
                    <span>{{ $editingSystemRole ? 'Rol base' : 'Rol personalizado' }}</span>
                    <h2 id="role-modal-title">{{ $editingRoleId ? 'Editar rol' : 'Crear rol' }}</h2>
                </div>
                <button type="button" wire:click="closeRoleModal" aria-label="Cerrar modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="saveRole" class="rm-form-stack">
                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Nombre del rol</span>
                        <input wire:model="roleName" type="text" placeholder="Coordinador">
                        @error('roleName') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="rm-field">
                        <span>Descripcion</span>
                        <input wire:model="roleDescription" type="text" placeholder="Que hace este rol">
                        @error('roleDescription') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="rm-permission-matrix">
                    <div class="rm-permission-head">
                        <span>Ventana</span>
                        @foreach ($actionLabels as $label)
                            <span>{{ $label }}</span>
                        @endforeach
                    </div>

                    @foreach ($modules as $moduleKey => $module)
                        <div class="rm-permission-line">
                            <div>
                                <strong>{{ $module['label'] }}</strong>
                                <span>{{ $module['group'] }}</span>
                            </div>
                            @foreach ($actionLabels as $action => $label)
                                <label class="rm-permission-check {{ in_array($action, $module['actions'], true) ? '' : 'is-disabled' }}">
                                    @if (in_array($action, $module['actions'], true))
                                        <input wire:model="rolePermissionChecks.{{ $moduleKey }}.{{ $action }}" type="checkbox">
                                    @else
                                        <input type="checkbox" disabled>
                                    @endif
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div class="rm-form-actions">
                    <button class="rm-button rm-button-primary" type="submit">{{ $editingRoleId ? 'Guardar rol' : 'Crear rol' }}</button>
                    <button class="rm-button rm-button-outline" type="button" wire:click="closeRoleModal">Cancelar</button>
                </div>
            </form>
        </section>
    @endif

    @if ($confirmingUserDeleteId)
        <div class="rm-modal-backdrop" wire:click="cancelDeleteUser"></div>
        <section class="rm-modal-panel rm-modal-panel-small" role="dialog" aria-modal="true">
            <div class="rm-confirm-icon">!</div>
            <h2>Deshabilitar ingreso</h2>
            <p>El usuario quedara inactivo. Si intenta ingresar, se le pedira comunicarse con administracion de su sucursal.</p>
            <div class="rm-form-actions">
                <button class="rm-button rm-button-danger" type="button" wire:click="deleteUser({{ $confirmingUserDeleteId }})">Deshabilitar ingreso</button>
                <button class="rm-button rm-button-outline" type="button" wire:click="cancelDeleteUser">Cancelar</button>
            </div>
        </section>
    @endif

    @if ($confirmingRoleDeleteId)
        <div class="rm-modal-backdrop" wire:click="cancelDeleteRole"></div>
        <section class="rm-modal-panel rm-modal-panel-small" role="dialog" aria-modal="true">
            <div class="rm-confirm-icon">!</div>
            <h2>Eliminar rol</h2>
            <p>Solo se puede eliminar si el rol no esta asignado a ningun usuario.</p>
            <div class="rm-form-actions">
                <button class="rm-button rm-button-danger" type="button" wire:click="deleteRole({{ $confirmingRoleDeleteId }})">Eliminar</button>
                <button class="rm-button rm-button-outline" type="button" wire:click="cancelDeleteRole">Cancelar</button>
            </div>
        </section>
    @endif
</div>
