import { Component, OnInit, AfterViewInit, OnDestroy, ViewChild, ElementRef, ChangeDetectorRef } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { CommonModule } from '@angular/common';
import AOS from 'aos';

@Component({
    selector: 'app-root',
    standalone: true,
    imports: [RouterOutlet, CommonModule],
    styles: [`
        .shimmer-text {
            background: linear-gradient(90deg, #1B365D 0%, #D4AF37 50%, #1B365D 100%);
            background-size: 200% auto;
            color: transparent;
            -webkit-background-clip: text;
            background-clip: text;
            animation: shimmer 3s linear infinite;
        }
        @keyframes shimmer {
            to { background-position: 200% center; }
        }
    `],
    template: `
      <!-- Canvas 3D Preloader -->
      <div *ngIf="visible" 
           class="fixed inset-0 z-[9999] bg-[#1B365D] flex flex-col items-center justify-center transition-opacity duration-700" 
           [class.opacity-0]="!loading"
           [class.pointer-events-none]="!loading">
           
         <canvas #canvas class="absolute inset-0 w-full h-full"></canvas>
      </div>

      <!-- Main Content -->
      <div [class.opacity-0]="loading" class="transition-opacity duration-1000">
         <router-outlet></router-outlet>
      </div>
    `
})
export class AppComponent implements OnInit, AfterViewInit, OnDestroy {
    loading = true;
    visible = true;
    
    @ViewChild('canvas') canvasRef!: ElementRef<HTMLCanvasElement>;
    private animationId: number | null = null;

    constructor(private cdr: ChangeDetectorRef) {}

    ngOnInit() {
        try {
            AOS.init({
                duration: 1000,
                easing: 'ease-out-cubic',
                once: true,
                mirror: false,
                offset: 50
            });
        } catch (e) {
            console.error('AOS init error', e);
        }

        setTimeout(() => {
            this.loading = false;
            this.cdr.detectChanges();
            setTimeout(() => {
                this.visible = false;
                this.stopAnimation();
                this.cdr.detectChanges();
                setTimeout(() => AOS.refresh(), 100);
            }, 700);
        }, 5000);
    }

    ngAfterViewInit() {
        if (this.visible) {
            this.initCanvasAnimation();
        }
    }

    ngOnDestroy() {
        this.stopAnimation();
    }

    private stopAnimation() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
            this.animationId = null;
        }
    }

    private initCanvasAnimation() {
        const canvas = this.canvasRef.nativeElement;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        let width = canvas.width = window.innerWidth;
        let height = canvas.height = window.innerHeight;

        window.addEventListener('resize', () => {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        });

        // 3D Sphere Config
        const particles: any[] = [];
        const particleCount = 150;
        const radius = Math.min(width, height) / 5;
        let rotationX = 0;
        let rotationY = 0;

        for (let i = 0; i < particleCount; i++) {
            const theta = Math.random() * 2 * Math.PI;
            const phi = Math.acos((Math.random() * 2) - 1);
            
            particles.push({
                x: radius * Math.sin(phi) * Math.cos(theta),
                y: radius * Math.sin(phi) * Math.sin(theta),
                z: radius * Math.cos(phi),
                size: Math.random() * 2 + 1,
                color: Math.random() > 0.5 ? '#FFFFFF' : '#D4AF37'
            });
        }

        const animate = () => {
            if (!this.visible) return;

            ctx.clearRect(0, 0, width, height);
            
            // Center origin
            const cx = width / 2;
            const cy = height / 2;

            // Rotate
            rotationY += 0.008;
            rotationX += 0.005;

            particles.forEach(p => {
                // Rotation Matrix
                let x = p.x;
                let y = p.y;
                let z = p.z;

                // Rotate around Y
                let x1 = x * Math.cos(rotationY) - z * Math.sin(rotationY);
                let z1 = z * Math.cos(rotationY) + x * Math.sin(rotationY);
                
                // Rotate around X
                let y1 = y * Math.cos(rotationX) - z1 * Math.sin(rotationX);
                let z2 = z1 * Math.cos(rotationX) + y * Math.sin(rotationX);

                // Project to 2D
                const scale = 400 / (400 + z2); // Perspective
                const x2d = x1 * scale + cx;
                const y2d = y1 * scale + cy;

                // Draw Particle
                const alpha = (z2 + radius) / (2 * radius); // Fade back particles
                ctx.globalAlpha = Math.max(0.1, alpha);
                ctx.fillStyle = p.color;
                ctx.beginPath();
                ctx.arc(x2d, y2d, p.size * scale, 0, Math.PI * 2);
                ctx.fill();

                // Draw Connections
                particles.forEach(p2 => {
                    // Simple distance check in 3D (expensive n^2 but okay for 150 particles)
                    const dx = x1 - (p2.x * Math.cos(rotationY) - p2.z * Math.sin(rotationY));
                    const dy = y1 - (p2.y * Math.cos(rotationX) - (p2.z * Math.cos(rotationY) + p2.x * Math.sin(rotationY)) * Math.sin(rotationX));
                    // Simplified connection logic for performance: just connect close 2D points
                });
            });

            // Draw Connections (Optimized: only connect if close in 2D projected space)
            // Re-looping for connections is heavy, let's just draw lines between close particles in the loop above? 
            // Better: separate loop for lines
            
            ctx.globalAlpha = 0.15;
            ctx.lineWidth = 0.5;
            ctx.strokeStyle = '#D4AF37';
            
            // To save CPU, we'll just connect random neighbors or pre-calculated links. 
            // For this demo, let's just connect points that are close in the array to simulate structure without N^2 check
            for(let i=0; i<particles.length; i++) {
                const p = particles[i];
                // Recalculate position for line drawing (redundant but cleaner code structure)
                let x = p.x; let y = p.y; let z = p.z;
                let x1 = x * Math.cos(rotationY) - z * Math.sin(rotationY);
                let z1 = z * Math.cos(rotationY) + x * Math.sin(rotationY);
                let y1 = y * Math.cos(rotationX) - z1 * Math.sin(rotationX);
                let z2 = z1 * Math.cos(rotationX) + y * Math.sin(rotationX);
                const scale = 400 / (400 + z2);
                const px = x1 * scale + cx;
                const py = y1 * scale + cy;

                // Connect to next 2 particles
                for(let j=1; j<=2; j++) {
                    const p2 = particles[(i+j) % particles.length];
                    let x_2 = p2.x; let y_2 = p2.y; let z_2 = p2.z;
                    let x1_2 = x_2 * Math.cos(rotationY) - z_2 * Math.sin(rotationY);
                    let z1_2 = z_2 * Math.cos(rotationY) + x_2 * Math.sin(rotationY);
                    let y1_2 = y_2 * Math.cos(rotationX) - z1_2 * Math.sin(rotationX);
                    let z2_2 = z1_2 * Math.cos(rotationX) + y_2 * Math.sin(rotationX);
                    const scale2 = 400 / (400 + z2_2);
                    const px2 = x1_2 * scale2 + cx;
                    const py2 = y1_2 * scale2 + cy;

                    const dist = Math.hypot(px - px2, py - py2);
                    if (dist < 100) {
                        ctx.beginPath();
                        ctx.moveTo(px, py);
                        ctx.lineTo(px2, py2);
                        ctx.stroke();
                    }
                }
            }

            this.animationId = requestAnimationFrame(animate);
        };

        animate();
    }
}
