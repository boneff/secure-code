<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\JoinColumn;

/**
 * Project
 *
 * @ORM\Table(name="projects")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProjectRepository")
 */
class Project
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="repository_url", type="string", length=255, unique=true)
     */
    private $repositoryUrl;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_created", type="datetime")
     */
    private $dateCreated;

    /**
     * Many Projects have Many Checks.
     * @ManyToMany(targetEntity="CodeCheck", inversedBy="projects")
     * @JoinTable(name="project_code_checks",
     *      joinColumns={@JoinColumn(name="project_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="code_check_id", referencedColumnName="id")}
     *      )
     */
    private $projectChecks;

    /**
     * Many Projects have Many Users.
     * @ManyToMany(targetEntity="User", mappedBy="projects")
     */
    private $users;

    public function __construct()
    {
        $this->projectChecks = new \Doctrine\Common\Collections\ArrayCollection();
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set repositoryUrl
     *
     * @param string $repositoryUrl
     *
     * @return Project
     */
    public function setRepositoryUrl($repositoryUrl)
    {
        $this->repositoryUrl = $repositoryUrl;

        return $this;
    }

    /**
     * Get repositoryUrl
     *
     * @return string
     */
    public function getRepositoryUrl()
    {
        return $this->repositoryUrl;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     *
     * @return Project
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * @return mixed
     */
    public function getProjectChecks()
    {
        return $this->projectChecks;
    }

    /**
     * @param mixed $projectChecks
     */
    public function setProjectChecks($projectChecks)
    {
        $this->projectChecks = $projectChecks;
    }

    /**
     * @return mixed
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param User $user
     */
    public function addUser(User $user)
    {
        $user->addProject($this); // synchronously updating inverse side
        $this->users[] = $user;
    }

    /**
     * @param CodeCheck $codeCheck
     */
    public function addCodeCheck(CodeCheck $codeCheck)
    {
        $codeCheck->addProject($this); // synchronously updating inverse side
        $this->projectChecks[] = $codeCheck;
    }
}

