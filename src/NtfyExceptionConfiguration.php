<?php

namespace Logger\Ntfy;

class NtfyExceptionConfiguration {
	/** @var list<string> */
	private array $applicationPaths;

	/**
	 * @param list<string> $applicationPaths
	 */
	public function __construct(
		private ?string $basePath = null,
		array $applicationPaths = [],
	) {
		$this->basePath = $this->normalizePath($this->basePath);
		$this->applicationPaths = $this->normalizeApplicationPaths($applicationPaths);
	}

	public function formatFile(string $file): string {
		$file = $this->normalizePath($file) ?? $file;

		if($this->basePath === null || !$this->isSameOrChildPath($file, $this->basePath)) {
			return $file;
		}

		$relative = ltrim(substr($file, strlen($this->basePath)), '/');
		return $relative === '' ? '.' : $relative;
	}

	public function isApplicationFile(string $file): bool {
		$file = $this->normalizePath($file);

		if($file === null) {
			return false;
		}

		foreach($this->applicationPaths as $path) {
			if($this->isSameOrChildPath($file, $path)) {
				return true;
			}
		}

		return false;
	}

	private function normalizePath(?string $path): ?string {
		if($path === null) {
			return null;
		}

		$path = rtrim(str_replace('\\', '/', trim($path)), '/');
		return $path === '' ? null : $path;
	}

	/**
	 * @param list<string> $paths
	 * @return list<string>
	 */
	private function normalizeApplicationPaths(array $paths): array {
		$normalizedPaths = [];

		if($this->basePath !== null && $paths === []) {
			$normalizedPaths[] = $this->basePath;
		}

		foreach($paths as $path) {
			$path = $this->normalizePath($path);
			if($path === null) {
				continue;
			}
			if($this->basePath !== null && !str_starts_with($path, '/')) {
				$path = "{$this->basePath}/{$path}";
			}

			$normalizedPaths[] = $path;
		}

		return array_values(array_unique($normalizedPaths));
	}

	private function isSameOrChildPath(string $file, string $path): bool {
		return $file === $path || str_starts_with($file, "{$path}/");
	}
}
