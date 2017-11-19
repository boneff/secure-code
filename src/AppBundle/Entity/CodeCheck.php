<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\JoinColumn;

/**
 * CodeCheck
 *
 * @ORM\Table(name="code_checks")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CodeCheckRepository")
 */
class CodeCheck
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
     * @ORM\Column(name="result", type="string", length=255)
     */
    private $result;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_secure", type="boolean")
     */
    private $isSecure;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_created", type="datetime")
     */
    private $dateCreated;

    /**
     * Many Checks have Many Vulnerabilities.
     * @ManyToMany(targetEntity="Vulnerability", inversedBy="codeChecks")
     * @JoinTable(name="code_check_vulnerabilities",
     *      joinColumns={@JoinColumn(name="code_check_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="vulnerability_id", referencedColumnName="id")}
     *      )
     */
    private $codeCheckVulnerabilities;

    public function __construct()
    {
        $this->codeCheckVulnerabilities = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set result
     *
     * @param string $result
     *
     * @return CodeCheck
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Get result
     *
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set isSecure
     *
     * @param boolean $isSecure
     *
     * @return CodeCheck
     */
    public function setIsSecure($isSecure)
    {
        $this->isSecure = $isSecure;

        return $this;
    }

    /**
     * Get isSecure
     *
     * @return bool
     */
    public function getIsSecure()
    {
        return $this->isSecure;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     *
     * @return CodeCheck
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
    public function getCodeCheckVulnerabilities()
    {
        return $this->codeCheckVulnerabilities;
    }

    /**
     * @param mixed $codeCheckVulnerabilities
     */
    public function setCodeCheckVulnerabilities($codeCheckVulnerabilities)
    {
        $this->codeCheckVulnerabilities = $codeCheckVulnerabilities;
    }
}

