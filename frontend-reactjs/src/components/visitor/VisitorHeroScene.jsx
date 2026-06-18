import { useEffect, useRef } from "react";

const NODE_COUNT = 46;
const THREE_MODULE_URL =
  "https://unpkg.com/three@0.184.0/build/three.module.js";

const createNode = (THREE, index) => {
  const ring = index % 4;
  const angle = (index / NODE_COUNT) * Math.PI * 2 * 3.2;
  const radius = 2.3 + ring * 0.72;
  const depth = ((index % 7) - 3) * 0.48;

  return {
    base: new THREE.Vector3(
      Math.cos(angle) * radius,
      Math.sin(angle * 0.88) * (1.05 + ring * 0.18),
      depth + Math.sin(angle * 1.7) * 0.44,
    ),
    phase: index * 0.47,
  };
};

export default function VisitorHeroScene() {
  const canvasRef = useRef(null);

  useEffect(() => {
    const canvas = canvasRef.current;
    if (!canvas) return undefined;

    let disposed = false;
    let cleanup = () => {};

    const initScene = async () => {
      const THREE = await import(/* @vite-ignore */ THREE_MODULE_URL);
      if (disposed) return;

    const prefersReducedMotion = window.matchMedia?.(
      "(prefers-reduced-motion: reduce)",
    )?.matches;
    const renderer = new THREE.WebGLRenderer({
      canvas,
      alpha: true,
      antialias: true,
      powerPreference: "high-performance",
    });

    renderer.setClearColor(0x000000, 0);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.7));

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(42, 1, 0.1, 100);
    camera.position.set(0, 0.18, 6.3);

    const group = new THREE.Group();
    scene.add(group);

    const nodeGeometry = new THREE.IcosahedronGeometry(0.055, 1);
    const nodeMaterials = [
      new THREE.MeshBasicMaterial({ color: 0xffffff }),
      new THREE.MeshBasicMaterial({ color: 0x73d7ff }),
      new THREE.MeshBasicMaterial({ color: 0xffd166 }),
      new THREE.MeshBasicMaterial({ color: 0xfff3c4 }),
    ];
    const nodes = Array.from({ length: NODE_COUNT }, (_, index) =>
      createNode(THREE, index),
    );
    const nodeMeshes = nodes.map((node, index) => {
      const mesh = new THREE.Mesh(nodeGeometry, nodeMaterials[index % 4]);
      mesh.position.copy(node.base);
      mesh.scale.setScalar(index % 5 === 0 ? 1.55 : 1);
      group.add(mesh);
      return mesh;
    });

    const lineMaterial = new THREE.LineBasicMaterial({
      color: 0x9ee7ff,
      transparent: true,
      opacity: 0.36,
    });
    const linePositions = new Float32Array((NODE_COUNT - 1) * 6);
    const lineGeometry = new THREE.BufferGeometry();
    lineGeometry.setAttribute(
      "position",
      new THREE.BufferAttribute(linePositions, 3),
    );
    const lines = new THREE.LineSegments(lineGeometry, lineMaterial);
    group.add(lines);

    const ringMaterial = new THREE.MeshBasicMaterial({
      color: 0xffffff,
      transparent: true,
      opacity: 0.18,
      wireframe: true,
    });
    const ringGeometry = new THREE.TorusKnotGeometry(1.2, 0.012, 90, 8, 2, 3);
    const rings = [0, 1, 2].map((index) => {
      const ring = new THREE.Mesh(ringGeometry, ringMaterial.clone());
      ring.position.set(2.8 - index * 2.8, 0.28 - index * 0.22, -1.3);
      ring.rotation.set(0.8 + index * 0.2, 0.1, index * 0.9);
      ring.scale.setScalar(0.72 + index * 0.18);
      group.add(ring);
      return ring;
    });

    const resize = () => {
      const rect = canvas.getBoundingClientRect();
      const width = Math.max(1, rect.width);
      const height = Math.max(1, rect.height);
      renderer.setSize(width, height, false);
      camera.aspect = width / height;
      camera.updateProjectionMatrix();
      group.scale.setScalar(width < 768 ? 1.38 : 1.82);
      group.position.x = width < 768 ? 0.04 : 1.32;
    };

    resize();
    const resizeObserver = new ResizeObserver(resize);
    resizeObserver.observe(canvas);

    let animationFrame = 0;
    const render = (time = 0) => {
      const seconds = time * 0.001;

      nodeMeshes.forEach((mesh, index) => {
        const node = nodes[index];
        mesh.position.set(
          node.base.x + Math.sin(seconds * 0.65 + node.phase) * 0.075,
          node.base.y + Math.cos(seconds * 0.58 + node.phase) * 0.06,
          node.base.z + Math.sin(seconds * 0.5 + node.phase) * 0.1,
        );
      });

      for (let index = 0; index < NODE_COUNT - 1; index += 1) {
        const source = nodeMeshes[index].position;
        const target = nodeMeshes[(index + 7) % NODE_COUNT].position;
        const offset = index * 6;
        linePositions[offset] = source.x;
        linePositions[offset + 1] = source.y;
        linePositions[offset + 2] = source.z;
        linePositions[offset + 3] = target.x;
        linePositions[offset + 4] = target.y;
        linePositions[offset + 5] = target.z;
      }
      lineGeometry.attributes.position.needsUpdate = true;

      group.rotation.y = Math.sin(seconds * 0.16) * 0.16;
      group.rotation.x = Math.cos(seconds * 0.13) * 0.045;
      rings.forEach((ring, index) => {
        ring.rotation.x += 0.0025 + index * 0.0008;
        ring.rotation.y += 0.0035 + index * 0.0007;
      });

      renderer.render(scene, camera);
      if (!prefersReducedMotion) {
        animationFrame = requestAnimationFrame(render);
      }
    };

    render();

    cleanup = () => {
      cancelAnimationFrame(animationFrame);
      resizeObserver.disconnect();
      nodeGeometry.dispose();
      lineGeometry.dispose();
      ringGeometry.dispose();
      nodeMaterials.forEach((material) => material.dispose());
      lineMaterial.dispose();
      rings.forEach((ring) => ring.material.dispose());
      renderer.dispose();
    };
    };

    initScene().catch(() => {});

    return () => {
      disposed = true;
      cleanup();
    };
  }, []);

  return (
    <canvas
      ref={canvasRef}
      className="absolute inset-0 h-full w-full"
      aria-hidden="true"
    />
  );
}
