import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { interval, Subscription } from 'rxjs';

interface Message {
  id: string;
  sender_id: string;
  sender_name: string;
  sender_role: string;
  sender_avatar?: string;
  subject: string;
  content: string;
  is_read: boolean;
  created_at: string;
  attachments?: string[];
}

interface Conversation {
  id: string;
  participant_id: string;
  participant_name: string;
  participant_role: string;
  participant_avatar?: string;
  last_message: string;
  last_message_date: string;
  unread_count: number;
}

@Component({
  selector: 'app-messages',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="messages-container">
      <!-- Sidebar -->
      <div class="messages-sidebar">
        <div class="sidebar-header">
          <h2>📨 Messagerie</h2>
          <button class="btn-new" (click)="openNewMessage()">+ Nouveau</button>
        </div>

        <div class="search-box">
          <input 
            type="text" 
            placeholder="Rechercher..."
            [(ngModel)]="searchQuery"
            (input)="filterConversations()">
        </div>

        <div class="conversations-list">
          <div 
            class="conversation-item"
            *ngFor="let conv of filteredConversations"
            [class.active]="selectedConversation?.id === conv.id"
            [class.unread]="conv.unread_count > 0"
            (click)="selectConversation(conv)">
            <div class="conv-avatar">
              <img [src]="conv.participant_avatar || defaultAvatar" alt="">
              <span class="status-dot" [class.online]="isOnline(conv.participant_id)"></span>
            </div>
            <div class="conv-info">
              <div class="conv-header">
                <span class="conv-name">{{ conv.participant_name }}</span>
                <span class="conv-time">{{ conv.last_message_date | date:'HH:mm' }}</span>
              </div>
              <div class="conv-preview">
                <span class="preview-text">{{ conv.last_message }}</span>
                <span class="unread-badge" *ngIf="conv.unread_count > 0">{{ conv.unread_count }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="messages-main">
        <!-- Conversation View -->
        <div class="conversation-view" *ngIf="selectedConversation && !showNewMessage">
          <div class="conv-header-bar">
            <div class="conv-info">
              <img [src]="selectedConversation.participant_avatar || defaultAvatar" class="conv-avatar-large">
              <div class="conv-details">
                <span class="conv-name-large">{{ selectedConversation.participant_name }}</span>
                <span class="conv-role">{{ getRoleName(selectedConversation.participant_role) }}</span>
              </div>
            </div>
            <div class="conv-actions">
              <button class="btn-icon" title="Archiver">📁</button>
              <button class="btn-icon" title="Supprimer">🗑️</button>
            </div>
          </div>

          <div class="messages-list" #messagesList>
            <div 
              class="message-item"
              *ngFor="let msg of messages"
              [class.sent]="msg.sender_id === currentUserId"
              [class.received]="msg.sender_id !== currentUserId">
              <div class="message-content">
                <div class="message-bubble">
                  <p>{{ msg.content }}</p>
                  <span class="message-time">{{ msg.created_at | date:'HH:mm' }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="message-input">
            <textarea 
              [(ngModel)]="newMessageText"
              placeholder="Écrire un message..."
              (keydown.enter)="sendMessage($event)"
              rows="1"></textarea>
            <button 
              class="btn-send" 
              (click)="sendMessage()"
              [disabled]="!newMessageText.trim()">
              ➤
            </button>
          </div>
        </div>

        <!-- New Message Form -->
        <div class="new-message-form" *ngIf="showNewMessage">
          <div class="form-header">
            <h3>Nouveau message</h3>
            <button class="btn-close" (click)="closeNewMessage()">×</button>
          </div>

          <div class="form-body">
            <div class="form-group">
              <label>Destinataire *</label>
              <select [(ngModel)]="newMessage.recipient_id">
                <option value="">Sélectionner...</option>
                <optgroup label="Direction">
                  <option *ngFor="let u of users.direction" [value]="u.id">{{ u.name }}</option>
                </optgroup>
                <optgroup label="Enseignants">
                  <option *ngFor="let u of users.teachers" [value]="u.id">{{ u.name }}</option>
                </optgroup>
                <optgroup label="Parents">
                  <option *ngFor="let u of users.parents" [value]="u.id">{{ u.name }}</option>
                </optgroup>
              </select>
            </div>

            <div class="form-group">
              <label>Objet *</label>
              <input type="text" [(ngModel)]="newMessage.subject" placeholder="Objet du message">
            </div>

            <div class="form-group">
              <label>Message *</label>
              <textarea 
                [(ngModel)]="newMessage.content"
                placeholder="Votre message..."
                rows="6"></textarea>
            </div>
          </div>

          <div class="form-footer">
            <button class="btn-secondary" (click)="closeNewMessage()">Annuler</button>
            <button class="btn-primary" (click)="sendNewMessage()" [disabled]="!canSendNewMessage()">
              Envoyer
            </button>
          </div>
        </div>

        <!-- Empty State -->
        <div class="empty-state" *ngIf="!selectedConversation && !showNewMessage">
          <div class="empty-icon">💬</div>
          <h3>Sélectionnez une conversation</h3>
          <p>Choisissez une conversation dans la liste ou créez-en une nouvelle.</p>
          <button class="btn-primary" (click)="openNewMessage()">+ Nouveau message</button>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .messages-container {
      display: flex;
      height: calc(100vh - 120px);
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
      margin: 1.5rem 2rem;
    }

    /* Sidebar */
    .messages-sidebar {
      width: 320px;
      border-right: 1px solid #e2e8f0;
      display: flex;
      flex-direction: column;
    }

    .sidebar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.25rem;
      border-bottom: 1px solid #e2e8f0;
    }

    .sidebar-header h2 {
      margin: 0;
      font-size: 1.1rem;
    }

    .btn-new {
      background: #4f46e5;
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      font-size: 0.85rem;
      cursor: pointer;
    }

    .search-box {
      padding: 0.75rem;
      border-bottom: 1px solid #e2e8f0;
    }

    .search-box input {
      width: 100%;
      padding: 0.625rem 1rem;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      font-size: 0.9rem;
    }

    .conversations-list {
      flex: 1;
      overflow-y: auto;
    }

    .conversation-item {
      display: flex;
      gap: 0.75rem;
      padding: 1rem;
      cursor: pointer;
      border-bottom: 1px solid #f1f5f9;
      transition: background 0.2s;
    }

    .conversation-item:hover {
      background: #f8fafc;
    }

    .conversation-item.active {
      background: #eff6ff;
      border-left: 3px solid #4f46e5;
    }

    .conversation-item.unread {
      background: #fef3c7;
    }

    .conv-avatar {
      position: relative;
    }

    .conv-avatar img {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      object-fit: cover;
    }

    .status-dot {
      position: absolute;
      bottom: 2px;
      right: 2px;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      border: 2px solid white;
      background: #94a3b8;
    }

    .status-dot.online {
      background: #10b981;
    }

    .conv-info {
      flex: 1;
      min-width: 0;
    }

    .conv-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.25rem;
    }

    .conv-name {
      font-weight: 600;
      color: #1e293b;
    }

    .conv-time {
      font-size: 0.75rem;
      color: #94a3b8;
    }

    .conv-preview {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .preview-text {
      font-size: 0.85rem;
      color: #64748b;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .unread-badge {
      background: #4f46e5;
      color: white;
      font-size: 0.7rem;
      padding: 0.125rem 0.5rem;
      border-radius: 10px;
      font-weight: 600;
    }

    /* Main Content */
    .messages-main {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .conversation-view {
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .conv-header-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 1.25rem;
      border-bottom: 1px solid #e2e8f0;
      background: #f8fafc;
    }

    .conv-header-bar .conv-info {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .conv-avatar-large {
      width: 44px;
      height: 44px;
      border-radius: 50%;
    }

    .conv-details {
      display: flex;
      flex-direction: column;
    }

    .conv-name-large {
      font-weight: 600;
      color: #1e293b;
    }

    .conv-role {
      font-size: 0.8rem;
      color: #64748b;
    }

    .conv-actions {
      display: flex;
      gap: 0.5rem;
    }

    .btn-icon {
      background: white;
      border: 1px solid #e2e8f0;
      padding: 0.5rem;
      border-radius: 6px;
      cursor: pointer;
    }

    .messages-list {
      flex: 1;
      overflow-y: auto;
      padding: 1.25rem;
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .message-item {
      display: flex;
    }

    .message-item.sent {
      justify-content: flex-end;
    }

    .message-item.received {
      justify-content: flex-start;
    }

    .message-bubble {
      max-width: 70%;
      padding: 0.75rem 1rem;
      border-radius: 16px;
    }

    .message-item.sent .message-bubble {
      background: #4f46e5;
      color: white;
      border-bottom-right-radius: 4px;
    }

    .message-item.received .message-bubble {
      background: #f1f5f9;
      color: #1e293b;
      border-bottom-left-radius: 4px;
    }

    .message-bubble p {
      margin: 0 0 0.25rem;
    }

    .message-time {
      font-size: 0.7rem;
      opacity: 0.7;
    }

    .message-input {
      display: flex;
      gap: 0.75rem;
      padding: 1rem;
      border-top: 1px solid #e2e8f0;
    }

    .message-input textarea {
      flex: 1;
      padding: 0.75rem 1rem;
      border: 1px solid #e2e8f0;
      border-radius: 24px;
      resize: none;
      font-family: inherit;
    }

    .btn-send {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: #4f46e5;
      color: white;
      border: none;
      cursor: pointer;
      font-size: 1.25rem;
    }

    .btn-send:disabled {
      background: #94a3b8;
      cursor: not-allowed;
    }

    /* New Message Form */
    .new-message-form {
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .form-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 1.25rem;
      border-bottom: 1px solid #e2e8f0;
    }

    .form-header h3 {
      margin: 0;
    }

    .btn-close {
      background: none;
      border: none;
      font-size: 1.5rem;
      color: #64748b;
      cursor: pointer;
    }

    .form-body {
      flex: 1;
      padding: 1.25rem;
      overflow-y: auto;
    }

    .form-group {
      margin-bottom: 1.25rem;
    }

    .form-group label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      color: #475569;
      margin-bottom: 0.5rem;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      font-size: 0.95rem;
    }

    .form-footer {
      display: flex;
      justify-content: flex-end;
      gap: 0.75rem;
      padding: 1rem 1.25rem;
      border-top: 1px solid #e2e8f0;
    }

    .btn-secondary {
      background: white;
      border: 1px solid #e2e8f0;
      padding: 0.625rem 1.25rem;
      border-radius: 8px;
      cursor: pointer;
    }

    .btn-primary {
      background: #4f46e5;
      color: white;
      border: none;
      padding: 0.625rem 1.25rem;
      border-radius: 8px;
      cursor: pointer;
    }

    .btn-primary:disabled {
      background: #94a3b8;
      cursor: not-allowed;
    }

    /* Empty State */
    .empty-state {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 2rem;
    }

    .empty-icon {
      font-size: 4rem;
      margin-bottom: 1rem;
    }

    .empty-state h3 {
      color: #1e293b;
      margin: 0 0 0.5rem;
    }

    .empty-state p {
      color: #64748b;
      margin: 0 0 1.5rem;
    }

    @media (max-width: 768px) {
      .messages-sidebar {
        width: 100%;
        position: absolute;
        z-index: 10;
        background: white;
      }
    }
  `]
})
export class MessagesComponent implements OnInit, OnDestroy {
  conversations: Conversation[] = [];
  filteredConversations: Conversation[] = [];
  messages: Message[] = [];
  searchQuery = '';

  selectedConversation: Conversation | null = null;
  showNewMessage = false;
  newMessageText = '';

  currentUserId = '';
  defaultAvatar = '/assets/images/default-avatar.png';

  users = {
    direction: [] as { id: string; name: string }[],
    teachers: [] as { id: string; name: string }[],
    parents: [] as { id: string; name: string }[],
  };

  newMessage = {
    recipient_id: '',
    subject: '',
    content: ''
  };

  private refreshSub?: Subscription;

  constructor(private http: HttpClient) {}

  ngOnInit() {
    this.loadCurrentUser();
    this.loadConversations();
    this.loadUsers();

    // Rafraîchir les conversations toutes les 30 secondes
    this.refreshSub = interval(30000).subscribe(() => {
      this.loadConversations();
    });
  }

  ngOnDestroy() {
    this.refreshSub?.unsubscribe();
  }

  loadCurrentUser() {
    this.http.get<any>('/api/auth/me')
      .subscribe(user => this.currentUserId = user.id);
  }

  loadConversations() {
    this.http.get<any>('/api/communication/conversations')
      .subscribe({
        next: (res) => {
          this.conversations = res.data || res;
          this.filterConversations();
        }
      });
  }

  loadUsers() {
    this.http.get<any>('/api/communication/recipients')
      .subscribe({
        next: (res) => {
          this.users = res;
        }
      });
  }

  filterConversations() {
    if (!this.searchQuery) {
      this.filteredConversations = this.conversations;
      return;
    }

    const query = this.searchQuery.toLowerCase();
    this.filteredConversations = this.conversations.filter(c =>
      c.participant_name.toLowerCase().includes(query) ||
      c.last_message.toLowerCase().includes(query)
    );
  }

  selectConversation(conv: Conversation) {
    this.selectedConversation = conv;
    this.showNewMessage = false;
    this.loadMessages(conv.id);
    this.markAsRead(conv.id);
  }

  loadMessages(conversationId: string) {
    this.http.get<any>(`/api/communication/conversations/${conversationId}/messages`)
      .subscribe({
        next: (res) => this.messages = res.data || res
      });
  }

  markAsRead(conversationId: string) {
    this.http.post(`/api/communication/conversations/${conversationId}/read`, {})
      .subscribe(() => {
        const conv = this.conversations.find(c => c.id === conversationId);
        if (conv) conv.unread_count = 0;
      });
  }

  sendMessage(event?: KeyboardEvent) {
    if (event && event.shiftKey) return; // Shift+Enter = nouvelle ligne
    if (event) event.preventDefault();

    if (!this.newMessageText.trim() || !this.selectedConversation) return;

    this.http.post('/api/communication/messages', {
      conversation_id: this.selectedConversation.id,
      content: this.newMessageText
    }).subscribe({
      next: (msg: any) => {
        this.messages.push(msg);
        this.newMessageText = '';
      }
    });
  }

  openNewMessage() {
    this.showNewMessage = true;
    this.selectedConversation = null;
    this.newMessage = { recipient_id: '', subject: '', content: '' };
  }

  closeNewMessage() {
    this.showNewMessage = false;
  }

  canSendNewMessage(): boolean {
    return !!(this.newMessage.recipient_id && this.newMessage.subject && this.newMessage.content);
  }

  sendNewMessage() {
    if (!this.canSendNewMessage()) return;

    this.http.post('/api/communication/messages', this.newMessage)
      .subscribe({
        next: () => {
          this.closeNewMessage();
          this.loadConversations();
        }
      });
  }

  getRoleName(role: string): string {
    const roles: { [key: string]: string } = {
      direction: 'Direction',
      secretariat: 'Secrétariat',
      enseignant: 'Enseignant',
      parent: 'Parent',
      eleve: 'Élève'
    };
    return roles[role] || role;
  }

  isOnline(userId: string): boolean {
    // TODO: Implémenter avec websockets
    return false;
  }
}
